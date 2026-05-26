import { useState, useCallback, useRef, useEffect } from "react";
import {
  View, ScrollView, Image, Pressable, StyleSheet, Dimensions,
  ActivityIndicator, FlatList, type ViewToken, Linking, Modal, Animated,
  TextInput, StatusBar, Platform, Share,
} from "react-native";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { LinearGradient } from "expo-linear-gradient";
import { ProductDetailSkeleton } from "@/components/skeleton";
import { Text, Sheet } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";
import * as FileSystem from "expo-file-system/legacy";
import * as MediaLibrary from "expo-media-library";
import * as Clipboard from "expo-clipboard";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const { width: SW, height: SH } = Dimensions.get("window");
const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";
const THUMB_SIZE = 52;
const DESC_PREVIEW_BLOCKS = 4;
const DESC_PREVIEW_CHARS = 360;
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

type NormalizedVariantSize = {
  sizeName: string;
  sizePrice: any;
  sizeStock: number;
  bulkPrices: any[];
};

type NormalizedVariant = {
  variantId: number;
  label: string;
  image?: string | null;
  colorCode?: string | null;
  stock: number;
  sizes: NormalizedVariantSize[];
};

type DescriptionBlock =
  | { type: "text"; text: string; variant: "body" | "heading"; level?: number }
  | { type: "listItem"; text: string; ordered: boolean; index?: number }
  | { type: "image"; uri: string; alt?: string };

function resolveDescriptionUrl(path: string): string {
  const raw = path.trim();
  if (!raw) return "";
  if (/^(?:https?:)?\/\//i.test(raw)) return raw.startsWith("//") ? `https:${raw}` : raw;
  if (/^(?:data:|mailto:|tel:|#)/i.test(raw)) return raw;

  const clean = raw.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

function decodeHtmlEntities(input: string): string {
  return input
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&#39;/gi, "'")
    .replace(/&quot;/gi, "\"")
    .replace(/&#(\d+);/g, (_match, code) => String.fromCharCode(Number(code)))
    .replace(/&#x([0-9a-f]+);/gi, (_match, code) => String.fromCharCode(Number.parseInt(code, 16)));
}

function stripHtmlToText(input: string): string {
  return input
    .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, " ")
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, " ")
    .replace(/<br\s*\/?>/gi, "\n")
    .replace(/<\/(p|div|li|h1|h2|h3|h4|h5|h6|tr)>/gi, "\n")
    .replace(/<[^>]*>/g, " ")
    .replace(/\u00A0/g, " ")
    .replace(/\s+$/gm, "")
    .split("\n")
    .map((line) => decodeHtmlEntities(line))
    .join("\n")
    .replace(/[ \t]+\n/g, "\n")
    .replace(/\n{3,}/g, "\n\n")
    .replace(/[ \t]{2,}/g, " ")
    .trim();
}

function getHtmlAttr(tag: string, attr: string): string {
  const match = tag.match(new RegExp(`${attr}\\s*=\\s*["']([^"']+)["']`, "i"));
  return match ? decodeHtmlEntities(match[1]) : "";
}

function htmlToReadableText(input: string): string {
  return stripHtmlToText(input).replace(/\n+/g, "\n").trim();
}

function pushTextBlock(blocks: DescriptionBlock[], input: string, variant: "body" | "heading" = "body", level?: number) {
  const text = htmlToReadableText(input);
  if (!text) return;

  if (variant === "heading") {
    blocks.push({ type: "text", text, variant, level });
    return;
  }

  text
    .split(/\n+/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
    .forEach((paragraph) => blocks.push({ type: "text", text: paragraph, variant: "body" }));
}

function parseDescriptionBlocks(input: string): DescriptionBlock[] {
  const trimmed = input.trim();
  if (!trimmed) return [];

  if (!/<\/?[a-z][\s\S]*>/i.test(trimmed)) {
    return trimmed
      .split(/\n{2,}/)
      .map((paragraph) => paragraph.trim())
      .filter(Boolean)
      .map((paragraph) => ({ type: "text", text: paragraph, variant: "body" }) as DescriptionBlock);
  }

  const sanitized = trimmed
    .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, "")
    .replace(/<style[\s\S]*?>[\s\S]*?<\/style>/gi, "")
    .replace(/\son[a-z]+\s*=\s*"[^"]*"/gi, "")
    .replace(/\son[a-z]+\s*=\s*'[^']*'/gi, "")
    .replace(/\son[a-z]+\s*=\s*[^\s>]+/gi, "");

  const blocks: DescriptionBlock[] = [];
  const tokens = sanitized
    .replace(/<\/(p|div|h[1-6]|li)>/gi, (tag) => `${tag}\n`)
    .split(/(<img\b[^>]*>|<h[1-6]\b[\s\S]*?<\/h[1-6]>|<li\b[\s\S]*?<\/li>)/gi)
    .filter((token) => token.trim().length > 0);

  let listIndex = 0;
  tokens.forEach((token) => {
    if (/^<img\b/i.test(token)) {
      const src = getHtmlAttr(token, "src") || getHtmlAttr(token, "data-src");
      if (src) blocks.push({ type: "image", uri: resolveDescriptionUrl(src), alt: getHtmlAttr(token, "alt") });
      return;
    }

    const headingMatch = token.match(/^<h([1-6])\b/i);
    if (headingMatch) {
      pushTextBlock(blocks, token, "heading", Number(headingMatch[1]));
      return;
    }

    if (/^<li\b/i.test(token)) {
      const text = htmlToReadableText(token);
      if (text) {
        listIndex += 1;
        blocks.push({ type: "listItem", text, ordered: false, index: listIndex });
      }
      return;
    }

    pushTextBlock(blocks, token);
  });

  return blocks;
}

function getDescriptionPreview(blocks: DescriptionBlock[], expanded: boolean) {
  if (expanded) return { blocks, canExpand: blocks.length > 0 };

  const visibleBlocks: DescriptionBlock[] = [];
  let usedChars = 0;

  for (const block of blocks) {
    if (visibleBlocks.length >= DESC_PREVIEW_BLOCKS) break;

    if (block.type === "image") {
      visibleBlocks.push(block);
      usedChars += 120;
      continue;
    }

    const remaining = DESC_PREVIEW_CHARS - usedChars;
    const text = block.text.trim();
    if (remaining <= 0) break;

    if (text.length > remaining) {
      visibleBlocks.push({ ...block, text: `${text.slice(0, remaining).trim()}...` });
      usedChars = DESC_PREVIEW_CHARS;
      break;
    }

    visibleBlocks.push(block);
    usedChars += text.length;
  }

  return {
    blocks: visibleBlocks.length > 0 ? visibleBlocks : blocks.slice(0, DESC_PREVIEW_BLOCKS),
    canExpand: visibleBlocks.length < blocks.length || usedChars >= DESC_PREVIEW_CHARS,
  };
}

function DescriptionImage({ uri }: { uri: string }) {
  const [aspectRatio, setAspectRatio] = useState(16 / 9);

  return (
    <Image
      source={{ uri }}
      style={[s.descImage, { aspectRatio }]}
      resizeMode="contain"
      onLoad={(event) => {
        const source = event.nativeEvent.source;
        if (!source?.width || !source?.height) return;
        const nextRatio = Math.min(Math.max(source.width / source.height, 0.55), 2.4);
        setAspectRatio(nextRatio);
      }}
    />
  );
}

function ProductDescriptionBlocks({ blocks }: { blocks: DescriptionBlock[] }) {
  return (
    <View style={s.descNativeWrap}>
      {blocks.map((block, index) => {
        if (block.type === "image") {
          return <DescriptionImage key={`${block.uri}-${index}`} uri={block.uri} />;
        }

        if (block.type === "listItem") {
          return (
            <View key={`${block.text}-${index}`} style={s.descListRow}>
              <Text style={s.descBullet}>{"\u2022"}</Text>
              <Text style={s.descListText}>{block.text}</Text>
            </View>
          );
        }

        if (block.variant === "heading") {
          const isLargeHeading = Number(block.level ?? 3) <= 2;
          return (
            <Text key={`${block.text}-${index}`} style={[s.descHeading, isLargeHeading && s.descHeadingLarge]}>
              {block.text}
            </Text>
          );
        }

        return (
          <Text key={`${block.text}-${index}`} style={s.descParagraph}>
            {block.text}
          </Text>
        );
      })}
    </View>
  );
}

export default function ProductDetailScreen() {
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const params = useLocalSearchParams<{ slug: string }>();
  const insets = useSafeAreaInsets();
  const slug = Array.isArray(params.slug) ? params.slug[0] : params.slug;
  const queryClient = useQueryClient();
  const [imgIdx, setImgIdx] = useState(0);
  const [activeTab, setActiveTab] = useState<"desc" | "specs">("desc");
  const [descExpanded, setDescExpanded] = useState(false);
  const [showActivation, setShowActivation] = useState(false);
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(null);
  const {
    isActive: isResellerActive,
    isLoggedIn,
    subscriptionDestination,
  } = useIsActiveReseller();
  const imgRef = useRef<FlatList>(null);
  const defaultQtySet = useRef(false);

  useEffect(() => {
    setDescExpanded(false);
  }, [slug]);

  // ── Variant / Size ordering state ──
  const [activeVariantIdx, setActiveVariantIdx] = useState(0);
  const [variantQuantities, setVariantQuantities] = useState<Record<number, Record<string, number>>>({});
  const [variantSellingPrices, setVariantSellingPrices] = useState<Record<number, Record<string, string>>>({});

  // ── Busy lock to prevent Buy Now / Add to Cart race condition ──
  const busyRef = useRef(false);
  const [isBusy, setIsBusy] = useState(false);

  // Scroll tracking for animated header
  const scrollY = useRef(new Animated.Value(0)).current;
  const HEADER_THRESHOLD = SW - 100; // Start fading in the solid header near end of hero image

  // ── Keyboard-aware (handled by KeyboardAwareScrollView) ──

  // Bottom sheet spring animation
  const sheetAnim = useRef(new Animated.Value(400)).current;
  useEffect(() => {
    if (showActivation) {
      sheetAnim.setValue(400);
      Animated.spring(sheetAnim, {
        toValue: 0,
        tension: 65,
        friction: 11,
        useNativeDriver: true,
      }).start();
    }
  }, [showActivation]);

  // Animated header interpolations
  const headerBg = scrollY.interpolate({
    inputRange: [HEADER_THRESHOLD - 60, HEADER_THRESHOLD],
    outputRange: ["rgba(255,255,255,0)", "rgba(255,255,255,1)"],
    extrapolate: "clamp",
  });
  const headerBorder = scrollY.interpolate({
    inputRange: [HEADER_THRESHOLD - 60, HEADER_THRESHOLD],
    outputRange: ["rgba(0,0,0,0)", "rgba(0,0,0,0.06)"],
    extrapolate: "clamp",
  });
  const headerTitleOpacity = scrollY.interpolate({
    inputRange: [HEADER_THRESHOLD - 30, HEADER_THRESHOLD + 10],
    outputRange: [0, 1],
    extrapolate: "clamp",
  });
  const heroOverlayBtnOpacity = scrollY.interpolate({
    inputRange: [HEADER_THRESHOLD - 60, HEADER_THRESHOLD],
    outputRange: [1, 0],
    extrapolate: "clamp",
  });

  const { data, isLoading, isError } = useQuery({
    queryKey: ["product-detail", slug],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/product-details/${slug}`);
      return d?.data ?? d;
    },
    enabled: !!slug && slug.length > 0,
  });

  // ── Shop hooks ──
  const rawProductId = (data?.product_details ?? data?.product ?? data)?.id;
  const productId = rawProductId ? Number(rawProductId) : null;

  const shopCheckQuery = useQuery({
    queryKey: ["check-in-shop", productId],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/check-in-shop/${productId}`);
      return d;
    },
    enabled: !!productId && isLoggedIn && isResellerActive,
    staleTime: 60 * 1000,
  });
  const isInShop = shopCheckQuery.data?.in_shop ?? false;

  const shopToggleMutation = useMutation({
    mutationFn: async (currentlyInShop: boolean) => {
      if (!productId) throw new Error("Product is still loading.");
      const endpoint = currentlyInShop ? `/remove-from-shop/${productId}` : `/add-to-shop/${productId}`;
      const { data: d } = await apiClient.get(endpoint);
      return d;
    },
    onSuccess: (_data, currentlyInShop) => {
      queryClient.invalidateQueries({ queryKey: ["check-in-shop", productId] });
      queryClient.invalidateQueries({ queryKey: ["shop-products"] });
      toast.success(currentlyInShop ? "Removed from your shop" : "Added to your shop!");
    },
    onError: (err: any) => {
      const message = String(err?.response?.data?.message ?? err?.message ?? "");
      if (message.toLowerCase().includes("already exist")) {
        queryClient.invalidateQueries({ queryKey: ["check-in-shop", productId] });
        queryClient.invalidateQueries({ queryKey: ["shop-products"] });
        toast.success("Already in your shop");
        return;
      }
      toast.error(message || "Something went wrong. Please try again.");
    },
  });
  const isShopActionBusy = shopCheckQuery.isLoading || shopCheckQuery.isFetching || shopToggleMutation.isPending;

  const handleToggleShop = () => {
    if (!isLoggedIn) {
      router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
      return;
    }
    if (!isResellerActive) {
      setShowActivation(true);
      return;
    }
    if (!productId) {
      toast.error("Product is still loading. Please try again.");
      return;
    }
    if (isShopActionBusy) return;
    shopToggleMutation.mutate(isInShop);
  };

  // ── Add to Cart mutation ──
  const addToCartMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data: d } = await apiClient.post("/user-add-to-cart", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return d;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
    },
    onError: () => {
      toast.error("Failed to add to cart");
    },
  });

  const onImgChange = useCallback(({ viewableItems }: { viewableItems: ViewToken[] }) => {
    if (viewableItems.length > 0 && viewableItems[0].index !== null) setImgIdx(viewableItems[0].index);
  }, []);
  const imgViewCfg = useRef({ viewAreaCoveragePercentThreshold: 50 }).current;

  // Set default quantity to 1 only for a simple, single-default-size product.
  // NOTE: Must be called before any early returns to satisfy Rules of Hooks
  useEffect(() => {
    if (!data || isLoading) return;
    const product = data.product_details ?? data.product ?? data;
    const variants: any[] = product.varients ?? [];
    const currentVariant = variants[activeVariantIdx];
    const currentVarId = currentVariant?.id ?? 0;
    const stockQty = Number(product.qty ?? 0);

    let sizesForEffect: Array<{ size_name: string; qty: number }> = [];
    if (currentVariant?.sizes && currentVariant.sizes.length > 0) {
      sizesForEffect = currentVariant.sizes.map((sz: any) => ({
        size_name: sz.size_name,
        qty: sz.qty ?? 0,
      }));
    } else {
      const overrideStock = currentVariant ? currentVariant.qty ?? stockQty : stockQty;
      const productSizes = Array.isArray(product.size)
        ? product.size
        : typeof product.size === "string"
          ? (() => { try { return JSON.parse(product.size); } catch { return []; } })()
          : [];
      const legacySizes = productSizes.length > 0 ? productSizes : ["Default"];
      sizesForEffect = legacySizes.map((sName: string) => ({
        size_name: sName,
        qty: overrideStock,
      }));
    }

    const isSimpleProduct =
      variants.length <= 1 &&
      sizesForEffect.length === 1 &&
      sizesForEffect[0]?.size_name === "Default";

    if (!defaultQtySet.current && isSimpleProduct && sizesForEffect.length > 0) {
      const firstSize = sizesForEffect[0];
      if (firstSize && firstSize.qty > 0) {
        setVariantQuantities((prev) => {
          if (Object.keys(prev).length === 0) {
            defaultQtySet.current = true;
            return { [currentVarId]: { [firstSize.size_name]: 1 } };
          }
          return prev;
        });
      }
    }
  }, [data, isLoading, activeVariantIdx]);

  // ── Image download handler (must be before early returns) ──
  const [downloading, setDownloading] = useState(false);
  const handleDownloadImage = useCallback(async (imageUri: string) => {
    if (downloading) return;
    try {
      setDownloading(true);
      const { status } = await MediaLibrary.requestPermissionsAsync();
      if (status !== "granted") {
        showDialog({ tone: "warning", title: "Permission needed", message: "Please allow access to save images to your gallery." });
        return;
      }
      const filename = imageUri.split("/").pop()?.split("?")[0] || `product-${Date.now()}.jpg`;
      const fileUri = `${FileSystem.cacheDirectory}${filename}`;
      const download = await FileSystem.downloadAsync(imageUri, fileUri);
      await MediaLibrary.saveToLibraryAsync(download.uri);
      toast.success("Image saved to gallery!");
    } catch (e) {
      console.warn("Download failed:", e);
      toast.error("Failed to download image");
    } finally {
      setDownloading(false);
    }
  }, [downloading, showDialog]);

  if (isLoading) return <ProductDetailSkeleton />;
  if (isError || !data) return (
    <View style={s.loadC}>
      <View style={s.errorIcon}>
        <Ionicons name="bag-remove-outline" size={48} color={ACCENT} />
      </View>
      <Text fontSize="$5" fontWeight="bold" color={DARK} mt="$3">Product Not Found</Text>
      <Text fontSize="$3" color={GREY} mt="$1" textAlign="center" style={{ maxWidth: 260 }}>
        This product may have been removed or is no longer available.
      </Text>
      <Pressable style={s.goBackBtn} onPress={() => router.back()}>
        <Ionicons name="arrow-back" size={16} color="#fff" />
        <Text fontSize="$3" fontWeight="600" color="#fff">Go Back</Text>
      </Pressable>
    </View>
  );

  const product = data.product_details ?? data.product ?? data;
  const relatedProducts: any[] = data.relatedproducts?.data ?? [];
  const flashSale = data.flash_sale;

  // Images
  const mainImg = product.ViewProductImage ?? product.ProductImage;
  let postImages: string[] = [];
  try { postImages = product.PostImage ? JSON.parse(product.PostImage) : []; } catch { postImages = []; }
  const allImgs: string[] = [mainImg, ...postImages].filter(Boolean);

  // Commission
  const commissionPercent = parseFloat(data.commission_percent || product.commission_percent || "0");
  const commissionFactor = 1 + (commissionPercent / 100);

  // Pricing
  const basePrice = Number(product.ProductResellerPrice || product.ProductRegularPrice || 0);
  const regularPrice = Number(product.ProductRegularPrice ?? 0);
  const flashPrice = Number(flashSale?.flash_price ?? 0);
  const flashOriginalPrice = Number(flashSale?.original_price ?? 0);
  const flashDiscountPercentage = Number(flashSale?.discount_percentage ?? 0);
  const priceTiers: any[] = product.price_tiers || [];
  const hasTiers = priceTiers.length > 0;

  // Variants
  const variants: any[] = product.varients ?? [];

  // Selling type
  const sellingType: "wholesale" | "dropshipping" | "both" = product.selling_type || "both";
  const supportsWholesale = sellingType === "wholesale" || sellingType === "both";
  const showWholesale = supportsWholesale && hasTiers;
  const showDropshipping = sellingType === "dropshipping" || sellingType === "both";

  // Meta
  const categoryName = product.categories?.category_name ?? "";
  const vendorName = product.vendor?.company_name || product.vendor?.CompanyName || "";
  const minQty = Number(product.minimum_qty ?? 1);
  const stockQty = Number(product.qty ?? 0);
  const sku = product.ProductSku ?? "";
  const youtubeLink = product.youtube_link ?? "";

  // Description
  const rawDesc = String(product.ProductDetails ?? product.ProductBreaf ?? "");
  const descriptionBlocks = parseDescriptionBlocks(rawDesc);
  const descriptionPreview = getDescriptionPreview(descriptionBlocks, descExpanded);
  const cleanDesc = stripHtmlToText(rawDesc);
  const hasDescription = descriptionBlocks.length > 0;
  const showReadMore = descriptionPreview.canExpand;
  const showCopyDescription = cleanDesc.length > 0;
  const showDescActions = showReadMore || showCopyDescription;

  // ── Copy description handler ──
  const handleCopyDescription = async () => {
    if (!cleanDesc) return;
    await Clipboard.setStringAsync(cleanDesc);
    toast.success("Description copied!");
  };
  // ── Computed sizes for current variant ──
  const currentVariant = variants[activeVariantIdx];
  const currentVarId = currentVariant?.id ?? 0;

  let sizesForTable: Array<{ size_name: string; price: any; qty: number; bulk_prices: any[] }> = [];
  if (currentVariant?.sizes && currentVariant.sizes.length > 0) {
    sizesForTable = currentVariant.sizes.map((sz: any) => ({
      size_name: sz.size_name,
      price: sz.price,
      qty: sz.qty ?? 0,
      bulk_prices: sz.bulkPrices || sz.bulk_prices || [],
    }));
  } else {
    const overrideStock = currentVariant ? currentVariant.qty ?? stockQty : stockQty;
    const productSizes = Array.isArray(product.size)
      ? product.size
      : typeof product.size === "string"
        ? (() => { try { return JSON.parse(product.size); } catch { return []; } })()
        : [];
    const legacySizes = productSizes.length > 0 ? productSizes : ["Default"];
    sizesForTable = legacySizes.map((sName: string) => ({
      size_name: sName,
      price: null,
      qty: overrideStock,
      bulk_prices: [],
    }));
  }

  const getVariantSizes = (variant?: any): NormalizedVariantSize[] => {
    if (variant?.sizes && variant.sizes.length > 0) {
      return variant.sizes.map((sz: any) => ({
        sizeName: sz.size_name,
        sizePrice: sz.price,
        sizeStock: Number(sz.qty ?? 0),
        bulkPrices: sz.bulkPrices || sz.bulk_prices || [],
      }));
    }

    const overrideStock = variant ? variant.qty ?? stockQty : stockQty;
    const productSizes = Array.isArray(product.size)
      ? product.size
      : typeof product.size === "string"
        ? (() => {
            try {
              return JSON.parse(product.size);
            } catch {
              return [];
            }
          })()
        : [];
    const legacySizes = productSizes.length > 0 ? productSizes : ["Default"];

    return legacySizes.map((sizeName: string) => ({
      sizeName,
      sizePrice: null,
      sizeStock: Number(overrideStock ?? 0),
      bulkPrices: [],
    }));
  };

  const normalizedVariants: NormalizedVariant[] =
    variants.length > 0
      ? variants.map((variant: any, idx: number) => {
          const sizes = getVariantSizes(variant);
          return {
            variantId: Number(variant.id),
            label: variant.color_name || variant.title || `Variant ${idx + 1}`,
            image: variant.image,
            colorCode: typeof variant.color_code === "string" ? variant.color_code : null,
            stock: Number(
              variant.qty ??
                sizes.reduce((sum, size) => sum + Number(size.sizeStock || 0), 0),
            ),
            sizes,
          };
        })
      : [
          {
            variantId: 0,
            label: "Default",
            image: null,
            colorCode: null,
            stock: stockQty,
            sizes: getVariantSizes(undefined),
          },
        ];

  const selectedVariant =
    normalizedVariants.find((variant) => variant.variantId === selectedVariantId) ??
    normalizedVariants[0];

  // Total quantity across all variants and sizes
  const totalQuantity = Object.values(variantQuantities)
    .flatMap((sizes) => Object.values(sizes))
    .reduce((a, b) => a + b, 0);

  const getTierMinQty = (tier: any): number => Number(tier?.min_qty ?? tier?.minQty ?? 0);
  const getTierMaxQty = (tier: any): number | null => {
    const rawMax = tier?.max_qty ?? tier?.maxQty;
    if (rawMax === undefined || rawMax === null || rawMax === "") return null;
    const maxQty = Number(rawMax);
    return Number.isFinite(maxQty) && maxQty > 0 ? maxQty : null;
  };
  const sortTiersByQty = (tiers: any[] = []) =>
    tiers.slice().sort((a: any, b: any) => getTierMinQty(a) - getTierMinQty(b));
  const findApplicableTier = (tiers: any[] = [], qty: number) => {
    const normalizedQty = Number(qty || 0);
    if (normalizedQty <= 0) return null;
    return tiers
      .slice()
      .sort((a: any, b: any) => getTierMinQty(b) - getTierMinQty(a))
      .find((tier: any) => {
        const minQty = getTierMinQty(tier);
        const maxQty = getTierMaxQty(tier);
        return normalizedQty >= minQty && (maxQty === null || normalizedQty <= maxQty);
      }) ?? null;
  };
  const getTierPrice = (tier: any): number =>
    Math.round(Number(tier?.bulk_price ?? tier?.unit_price ?? 0) * commissionFactor * 100) / 100;
  const getTierQtyLabel = (tier: any): string => {
    const minQty = getTierMinQty(tier);
    const maxQty = getTierMaxQty(tier);
    return maxQty ? `${minQty}-${maxQty} pcs` : `${minQty}+ pcs`;
  };
  const isSameTier = (left: any, right: any): boolean => {
    if (!left || !right) return false;
    if (left.id !== undefined && right.id !== undefined) return String(left.id) === String(right.id);
    return left === right;
  };

  // Active tier based on total qty
  const activeTier = hasTiers ? findApplicableTier(priceTiers, totalQuantity) : null;

  // Get price for a size item
  const getSizePrice = (sizeItem: any, qty: number): number => {
    if (flashSale && flashSale.flash_price > 0) {
      return parseFloat(flashSale.flash_price);
    }
    // Size-level bulk tiers
    const tiers = sizeItem.bulkPrices || sizeItem.bulk_prices || [];
    if (tiers.length > 0) {
      const tier = findApplicableTier(tiers, qty);
      if (tier) return getTierPrice(tier);
    }
    // Size-level base price
    if (sizeItem.price !== null && sizeItem.price !== undefined) {
      const sp = parseFloat(sizeItem.price);
      if (sp > 0) return Math.round(sp * commissionFactor * 100) / 100;
    }
    // Product-level tier
    if (hasTiers && activeTier) {
      return getTierPrice(activeTier);
    }
    // Fallback
    return Math.round(basePrice * commissionFactor * 100) / 100;
  };

  // Display price (for hero section)
  const salePrice = flashPrice > 0
    ? flashPrice
    : activeTier
      ? getTierPrice(activeTier)
      : Math.round(
          Number(product.storefront_price ?? product.ProductSalePrice ?? basePrice) *
            (product.storefront_price ? 1 : commissionFactor) *
            100
        ) / 100;

  const originalPrice = flashPrice > 0 && flashOriginalPrice > 0
    ? flashOriginalPrice
    : regularPrice;
  const hasDiscount = originalPrice > salePrice && originalPrice > 0;
  const discountPercent = hasDiscount
    ? Math.round(flashPrice > 0 && flashDiscountPercentage > 0
      ? flashDiscountPercentage
      : ((originalPrice - salePrice) / originalPrice) * 100)
    : 0;

  const getBulkTierSizeForVariant = (variant?: NormalizedVariant | null): NormalizedVariantSize | null => {
    if (!variant) return null;
    const selectedBulkSize = variant.sizes.find((sizeItem) => {
      const qty = variantQuantities[variant.variantId]?.[sizeItem.sizeName] || 0;
      return qty > 0 && sizeItem.bulkPrices.length > 0;
    });
    return selectedBulkSize ?? variant.sizes.find((sizeItem) => sizeItem.bulkPrices.length > 0) ?? null;
  };
  const activeVariantForBulkTiers = normalizedVariants[activeVariantIdx] ?? normalizedVariants[0];
  const activeSizeBulkItem = getBulkTierSizeForVariant(activeVariantForBulkTiers);
  const activeSizeBulkQty = activeSizeBulkItem && activeVariantForBulkTiers
    ? variantQuantities[activeVariantForBulkTiers.variantId]?.[activeSizeBulkItem.sizeName] || 0
    : 0;
  const activeSizeBulkTier = activeSizeBulkItem
    ? findApplicableTier(activeSizeBulkItem.bulkPrices, activeSizeBulkQty)
    : null;
  const selectedSheetBulkSize = getBulkTierSizeForVariant(selectedVariant);
  const selectedSheetBulkQty = selectedSheetBulkSize
    ? variantQuantities[selectedVariant.variantId]?.[selectedSheetBulkSize.sizeName] || 0
    : 0;
  const selectedSheetBulkTier = selectedSheetBulkSize
    ? findApplicableTier(selectedSheetBulkSize.bulkPrices, selectedSheetBulkQty)
    : null;

  // ── Qty handlers ──
  const handleQtyChange = (variantId: number, size: string, type: "increase" | "decrease", stock?: number) => {
    setVariantQuantities((prev) => {
      const varSizes = { ...(prev[variantId] || {}) };
      const cur = varSizes[size] || 0;
      let next = type === "increase" ? cur + 1 : Math.max(0, cur - 1);
      if (type === "increase" && stock !== undefined && next > stock) {
        next = stock;
        toast.error(`Only ${stock} items in stock for size ${size}`);
      }
      varSizes[size] = next;
      return { ...prev, [variantId]: varSizes };
    });
  };

  const handleDirectQtyInput = (variantId: number, size: string, value: string, stock?: number) => {
    const cleaned = value.replace(/[^0-9]/g, "");
    const parsed = cleaned === "" ? 0 : parseInt(cleaned, 10);
    let clamped = Math.max(0, parsed);
    if (stock !== undefined && clamped > stock) {
      clamped = stock;
      toast.error(`Only ${stock} items in stock for size ${size}`);
    }
    setVariantQuantities((prev) => {
      const varSizes = { ...(prev[variantId] || {}) };
      varSizes[size] = clamped;
      return { ...prev, [variantId]: varSizes };
    });
  };

  const handleSellingPriceChange = (variantId: number, size: string, value: string) => {
    setVariantSellingPrices((prev) => {
      const varSizes = { ...(prev[variantId] || {}) };
      varSizes[size] = value;
      return { ...prev, [variantId]: varSizes };
    });
  };

  // ── Get selected items for cart submission ──
  const getSelectedItems = () => {
    const items: { variantId: number; variantTitle: string; size: string; qty: number; price: number; sellingPrice: number | null }[] = [];
    for (const [vid, sizes] of Object.entries(variantQuantities)) {
      const v = normalizedVariants.find((variant) => variant.variantId === Number(vid));
      for (const [sizeName, qty] of Object.entries(sizes)) {
        if (qty > 0) {
          const variantLabel = v?.label || "";
          const sizeItem = v?.sizes.find((sz) => sz.sizeName === sizeName);
          const itemPrice = sizeItem
            ? getSizePrice(
                {
                  price: sizeItem.sizePrice,
                  qty: sizeItem.sizeStock,
                  bulk_prices: sizeItem.bulkPrices,
                },
                qty,
              )
            : salePrice;
          items.push({
            variantId: Number(vid),
            variantTitle: variantLabel,
            size: sizeName,
            qty,
            price: itemPrice,
            sellingPrice: null, // Will be derived from total below
          });
        }
      }
    }

    // Derive per-unit selling price from the total selling price input
    if (items.length > 0 && showDropshipping) {
      const fallbackVarId = variants.length > 0 ? (variants[0]?.id ?? 0) : 0;
      const fallbackSizes = variants.length > 0 && variants[0]?.sizes?.length > 0
        ? variants[0].sizes.map((sz: any) => sz.size_name)
        : sizesForTable.map((sz) => sz.size_name);
      const singleSP = variantSellingPrices[fallbackVarId]?.[fallbackSizes[0]] || "";
      const totalSP = singleSP ? parseFloat(singleSP) : 0;
      if (totalSP > 0) {
        const totalCost = items.reduce((sum, item) => sum + item.price * item.qty, 0);
        const totalQtyAll = items.reduce((sum, item) => sum + item.qty, 0);
        if (totalQtyAll > 0 && totalCost > 0) {
          // Distribute proportionally: each item gets selling_price = costPrice × (totalSP / totalCost)
          const ratio = totalSP / totalCost;
          for (const item of items) {
            item.sellingPrice = Math.round(item.price * ratio * 100) / 100;
          }
        }
      }
    }

    return items;
  };

  // ── Validate total selling price ──
  const validateTotalSellingPrice = (): boolean => {
    const items = getSelectedItems();
    const totalCost = items.reduce((sum, item) => sum + item.price * item.qty, 0);
    const fallbackVarId = variants.length > 0 ? (variants[0]?.id ?? 0) : 0;
    const fallbackSizes = variants.length > 0 && variants[0]?.sizes?.length > 0
      ? variants[0].sizes.map((sz: any) => sz.size_name)
      : sizesForTable.map((sz) => sz.size_name);
    const singleSP = variantSellingPrices[fallbackVarId]?.[fallbackSizes[0]] || "";
    const totalSP = singleSP ? parseFloat(singleSP) : 0;
    if (!totalSP || totalSP < totalCost) {
      toast.error(`Please enter a total selling price ≥ ৳${Math.ceil(totalCost)}`);
      return false;
    }
    return true;
  };

  // ── Add to Cart ──
  const handleAddToCart = async () => {
    if (busyRef.current) return;
    if (!isLoggedIn) {
      router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
      return;
    }
    if (!isResellerActive) {
      setShowActivation(true);
      return;
    }

    const items = getSelectedItems();
    if (items.length === 0) {
      toast.error("Please select at least one item");
      return;
    }

    if (showDropshipping && !validateTotalSellingPrice()) {
      return;
    }

    busyRef.current = true;
    setIsBusy(true);
    try {
      let successCount = 0;
      for (const item of items) {
        const formData = new FormData();
        formData.append("product_id", String(product.id));
        formData.append("price", item.price.toString());
        if (showDropshipping && item.sellingPrice) {
          formData.append("selling_price", item.sellingPrice.toString());
        }
        formData.append("qty", item.qty.toString());
        formData.append("size", item.size);
        if (item.variantId) formData.append("varient_id", item.variantId.toString());
        if (item.variantTitle) formData.append("color", item.variantTitle);

        try {
          await addToCartMutation.mutateAsync(formData);
          successCount++;
        } catch { /* error handled in mutation */ }
      }
      if (successCount > 0) {
        toast.success(`${successCount} item${successCount > 1 ? "s" : ""} added to cart`);
        // Reset quantities after adding
        setVariantQuantities({});
        setVariantSellingPrices({});
      }
    } finally {
      busyRef.current = false;
      setIsBusy(false);
    }
  };

  // ── Buy Now ──
  const handleBuyNow = async () => {
    if (busyRef.current) return;
    if (!isLoggedIn) {
      router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
      return;
    }
    if (!isResellerActive) {
      setShowActivation(true);
      return;
    }

    const items = getSelectedItems();
    if (items.length === 0) {
      toast.error("Please select at least one item");
      return;
    }

    if (showDropshipping && !validateTotalSellingPrice()) {
      return;
    }

    busyRef.current = true;
    setIsBusy(true);
    try {
      let lastSuccess = false;
      for (const item of items) {
        const formData = new FormData();
        formData.append("product_id", String(product.id));
        formData.append("price", item.price.toString());
        if (showDropshipping && item.sellingPrice) {
          formData.append("selling_price", item.sellingPrice.toString());
        }
        formData.append("qty", item.qty.toString());
        formData.append("size", item.size);
        if (item.variantId) formData.append("varient_id", item.variantId.toString());
        if (item.variantTitle) formData.append("color", item.variantTitle);

        try {
          await addToCartMutation.mutateAsync(formData);
          lastSuccess = true;
        } catch { /* error handled */ }
      }

      if (lastSuccess) {
        setSelectedVariantId(null); // close variant sheet before navigating
        router.push("/order-confirmation" as any);
      }
    } finally {
      busyRef.current = false;
      setIsBusy(false);
    }
  };

  const scrollToImage = (idx: number) => {
    imgRef.current?.scrollToIndex({ index: idx, animated: true });
    setImgIdx(idx);
  };

  const formatBDT = (num: number, dec = 2) => num.toLocaleString("en-BD", { minimumFractionDigits: dec, maximumFractionDigits: dec });
  const productSlug = String(product.ProductSlug ?? slug ?? "").trim();
  const productUrl = productSlug
    ? `https://selfshop.com.bd/product/${encodeURIComponent(productSlug)}`
    : "https://selfshop.com.bd";
  const productName = String(product.ProductName ?? "SelfShop product");
  const handleShareProduct = async () => {
    try {
      await Share.share({
        title: productName,
        message: isResellerActive
          ? `${productName} - \u09F3${formatBDT(salePrice, 0)}\n${productUrl}`
          : `${productName}\n${productUrl}`,
        url: productUrl,
      });
    } catch (error) {
      console.warn("Product share failed:", error);
      toast.error("Failed to open share sheet");
    }
  };
  const selectedItemsTotal = getSelectedItems().reduce((sum, item) => sum + item.price * item.qty, 0);
  const selectedVariantSummary = normalizedVariants.flatMap((variant) =>
    Object.entries(variantQuantities[variant.variantId] ?? {})
      .filter(([, qty]) => Number(qty || 0) > 0)
      .map(([sizeName, qty]) => `${variant.label} / ${sizeName} x${qty}`),
  );
  const openVariantSheet = (variantId: number) => {
    const nextIdx = normalizedVariants.findIndex((variant) => variant.variantId === variantId);
    if (nextIdx >= 0) setActiveVariantIdx(nextIdx);
    setSelectedVariantId(variantId);
  };
  const closeVariantSheet = () => setSelectedVariantId(null);

  return (
    <View style={s.container}>
      <StatusBar translucent backgroundColor="transparent" barStyle="dark-content" />

      <KeyboardAwareScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: 130 }}
        scrollEventThrottle={16}
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        keyboardShouldPersistTaps="handled"
        bottomOffset={80}
        onScroll={Animated.event(
          [{ nativeEvent: { contentOffset: { y: scrollY } } }],
          { useNativeDriver: false }
        )}
      >

        {/* ═══ IMAGE GALLERY ═══ */}
        <View style={s.hero}>
          <FlatList
            ref={imgRef} data={allImgs} horizontal pagingEnabled
            showsHorizontalScrollIndicator={false} bounces={false}
            onViewableItemsChanged={onImgChange} viewabilityConfig={imgViewCfg}
            keyExtractor={(_, i) => String(i)}
            renderItem={({ item }) => (
              <View style={{ width: SW, position: "relative" }}>
                <Image source={{ uri: item }} style={s.heroImg} resizeMode="contain" />
                <Pressable
                  style={({ pressed }) => [s.downloadBadge, pressed && { opacity: 0.7 }]}
                  onPress={() => handleDownloadImage(item)}
                  disabled={downloading}
                >
                  <Ionicons name="download-outline" size={16} color="#1A1A2E" />
                  <Text style={s.downloadBadgeText}>{downloading ? "Saving..." : "Download"}</Text>
                </Pressable>
              </View>
            )}
          />
          {/* Gradient overlay for status bar readability */}
          <LinearGradient
            colors={["rgba(0,0,0,0.35)", "rgba(0,0,0,0.08)", "transparent"]}
            style={[s.heroGradient, { height: insets.top + 50 }]}
            pointerEvents="none"
          />
          {/* Floating buttons on hero (fade out as header appears) */}
          <Animated.View style={[s.overlayBtn, { top: insets.top + 8, left: 16, opacity: heroOverlayBtnOpacity }]} pointerEvents="auto">
            <Pressable onPress={() => router.back()} hitSlop={8}>
              <Ionicons name="arrow-back" size={22} color={DARK} />
            </Pressable>
          </Animated.View>
          <Animated.View
            style={[s.overlayBtn, { top: insets.top + 8, right: 60, backgroundColor: isInShop ? "#D1FAE5" : "rgba(255,255,255,0.95)", opacity: heroOverlayBtnOpacity }]}
            pointerEvents="auto"
          >
            <Pressable style={s.iconOnlyBtn} onPress={handleToggleShop} disabled={isShopActionBusy} hitSlop={8}>
              {isShopActionBusy ? (
                <ActivityIndicator size="small" color={isInShop ? "#059669" : DARK} />
              ) : (
                <Ionicons name={isInShop ? "storefront" : "storefront-outline"} size={18} color={isInShop ? "#059669" : DARK} />
              )}
            </Pressable>
          </Animated.View>
          <Animated.View style={[s.overlayBtn, { top: insets.top + 8, right: 16, opacity: heroOverlayBtnOpacity }]} pointerEvents="auto">
            <Pressable
              hitSlop={8}
              onPress={handleShareProduct}
            >
              <Ionicons name="share-social-outline" size={20} color={DARK} />
            </Pressable>
          </Animated.View>
          {allImgs.length > 1 && (
            <View style={s.imgCounter}>
              <Text fontSize={11} fontWeight="600" color="#fff">{imgIdx + 1}/{allImgs.length}</Text>
            </View>
          )}
        </View>

        {/* Thumbnails */}
        {allImgs.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={s.thumbRow}>
            {allImgs.map((img, i) => (
              <Pressable key={i} onPress={() => scrollToImage(i)}>
                <Image source={{ uri: img }} style={[s.thumbImg, imgIdx === i && s.thumbActive]} resizeMode="cover" />
              </Pressable>
            ))}
          </ScrollView>
        )}

        {/* ═══ PRICE + TITLE CARD ═══ */}
        <View style={s.mainCard}>
          {/* Flash Sale Banner */}
          {flashSale && (
            <LinearGradient
              colors={["#b3003b", "#E5005F"]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={s.flashBanner}
            >
              <View style={s.flashLeft}>
                <Text fontSize={16}>⚡</Text>
                <View>
                  <Text fontSize={10} fontWeight="800" color="rgba(255,255,255,0.8)" style={{ textTransform: "uppercase", letterSpacing: 1, fontStyle: "italic" }}>Flash Sale</Text>
                  <Text fontSize="$3" fontWeight="bold" color="#fff" fontStyle="italic">{flashSale.flash_sale_title ?? "Limited Time Offer"}</Text>
                </View>
              </View>
            </LinearGradient>
          )}

          {/* Selling Type Badge */}
          <View style={{ flexDirection: "row", gap: 6, marginBottom: 8 }}>
            {sellingType === "wholesale" && (
              <View style={[s.typeBadge, { backgroundColor: "#ECFDF5" }]}>
                <Text fontSize={10} fontWeight="700" color="#059669">🏭 Wholesale</Text>
              </View>
            )}
            {sellingType === "dropshipping" && (
              <View style={[s.typeBadge, { backgroundColor: "#EFF6FF" }]}>
                <Text fontSize={10} fontWeight="700" color="#2563EB">🚀 Dropshipping</Text>
              </View>
            )}
            {sellingType === "both" && (
              <View style={[s.typeBadge, { backgroundColor: "#FFFBEB" }]}>
                <Text fontSize={10} fontWeight="700" color="#D97706">🔄 Wholesale + Dropshipping</Text>
              </View>
            )}
          </View>

          {/* Price */}
          <View style={s.priceSection}>
            {isResellerActive ? (
              <>
                <Text fontSize={32} fontWeight="800" color={ACCENT} style={{ letterSpacing: -1 }}>
                  ৳{formatBDT(salePrice, 0)}
                </Text>
                {hasDiscount && (
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 8, marginTop: 2 }}>
                    <Text fontSize="$3" color={GREY} textDecorationLine="line-through">
                      ৳{originalPrice.toLocaleString()}
                    </Text>
                    <View style={s.discountBadge}>
                      <Text fontSize={11} fontWeight="800" color="#fff">-{discountPercent}%</Text>
                    </View>
                  </View>
                )}
              </>
            ) : (
              <View style={{ flexDirection: "row", alignItems: "center", gap: 10 }}>
                <Text fontSize={28} fontWeight="800" color="#999">***</Text>
                <View style={s.lockBadge}>
                  <Ionicons name="lock-closed" size={12} color={ACCENT} />
                  <Text fontSize={11} fontWeight="700" color={ACCENT}>
                    {isLoggedIn ? "Active profile required" : "Login to see price"}
                  </Text>
                </View>
              </View>
            )}
          </View>

          {/* Name */}
          <Text fontSize={18} fontWeight="600" color={DARK} mt="$2" lineHeight={24}>
            {product.ProductName}
          </Text>

          {/* Stock + SKU row */}
          <View style={s.metaRow}>
            {stockQty > 0 ? (
              <View style={[s.metaChip, { backgroundColor: "#E8F5E9" }]}>
                <View style={[s.metaDot, { backgroundColor: "#4CAF50" }]} />
                <Text fontSize={11} fontWeight="600" color="#2E7D32">In Stock</Text>
              </View>
            ) : (
              <View style={[s.metaChip, { backgroundColor: "#FFEBEE" }]}>
                <View style={[s.metaDot, { backgroundColor: "#E53935" }]} />
                <Text fontSize={11} fontWeight="600" color="#C62828">Out of Stock</Text>
              </View>
            )}
            {sku ? <View style={s.metaChip}><Text fontSize={11} color="#666">SKU: {sku}</Text></View> : null}
            {categoryName ? <View style={s.metaChip}><Text fontSize={11} color="#666">{categoryName}</Text></View> : null}
          </View>
        </View>

        {/* ═══ WHOLESALE BULK TIER BADGES ═══ */}
        {supportsWholesale && isResellerActive && activeSizeBulkItem && activeVariantForBulkTiers && (
          <View style={s.sizeTierCard}>
            <View style={s.sizeTierHeader}>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 6, flex: 1 }}>
                <Ionicons name="pricetag-outline" size={15} color={ACCENT} />
                <Text fontSize={12} fontWeight="800" color={ACCENT} style={{ textTransform: "uppercase" }}>
                  Bulk discounts for size {activeSizeBulkItem.sizeName}
                </Text>
              </View>
              {activeSizeBulkQty > 0 && (
                <View style={s.sizeTierQtyBadge}>
                  <Text fontSize={10} fontWeight="800" color={ACCENT}>{activeSizeBulkQty} pcs</Text>
                </View>
              )}
            </View>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={s.sizeTierList}
            >
              {sortTiersByQty(activeSizeBulkItem.bulkPrices).map((tier: any, tierIdx: number) => {
                const isActive = isSameTier(activeSizeBulkTier, tier);
                return (
                  <View
                    key={tier.id ?? tierIdx}
                    style={[s.sizeTierBadge, isActive && s.sizeTierBadgeActive]}
                  >
                    <Text fontSize={11} fontWeight="800" color={isActive ? "#fff" : ACCENT}>
                      {getTierQtyLabel(tier)}: {"\u09F3"}{formatBDT(getTierPrice(tier), 0)}
                    </Text>
                  </View>
                );
              })}
            </ScrollView>
            {activeSizeBulkQty > 0 && activeSizeBulkTier ? (
              <View style={s.sizeTierMessage}>
                <Ionicons name="checkmark-circle" size={14} color="#059669" />
                <Text fontSize={12} fontWeight="700" color="#065F46">
                  Size {activeSizeBulkItem.sizeName} discount applied automatically
                </Text>
              </View>
            ) : null}
          </View>
        )}

        {showWholesale && isResellerActive && (
          <View style={s.card}>
            <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginBottom: 12 }}>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
                <Ionicons name="layers-outline" size={16} color={ACCENT} />
                <Text fontSize={14} fontWeight="700" color={DARK}>Wholesale Pricing</Text>
              </View>
              {totalQuantity > 0 && (
                <View style={{ backgroundColor: "#FFF0F5", borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 }}>
                  <Text fontSize={11} fontWeight="700" color={ACCENT}>{totalQuantity} pcs selected</Text>
                </View>
              )}
            </View>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
              {sortTiersByQty(priceTiers).map((tier: any, tierIdx: number) => {
                const isActive = isSameTier(activeTier, tier);
                const qtyLabel = getTierQtyLabel(tier);
                const tierPrice = getTierPrice(tier);
                return (
                  <View
                    key={tier.id ?? tierIdx}
                    style={[
                      s.tierBadge,
                      isActive && { borderColor: ACCENT, backgroundColor: "#FFF0F5", transform: [{ scale: 1.04 }] },
                    ]}
                  >
                    {isActive && (
                      <View style={{ position: "absolute", top: -6, right: -6, width: 18, height: 18, borderRadius: 9, backgroundColor: ACCENT, justifyContent: "center", alignItems: "center" }}>
                        <Ionicons name="checkmark" size={12} color="#fff" />
                      </View>
                    )}
                    <Text fontSize={14} fontWeight="800" color={isActive ? ACCENT : DARK}>
                      ৳{formatBDT(tierPrice, 0)}
                    </Text>
                    <Text fontSize={10} fontWeight="600" color={isActive ? ACCENT : GREY}>{qtyLabel}</Text>
                  </View>
                );
              })}
            </ScrollView>
            {/* Contextual tier message */}
            {(() => {
              const nextTier = sortTiersByQty(priceTiers).find((tier: any) => getTierMinQty(tier) > totalQuantity);
              if (nextTier && totalQuantity > 0) {
                const moreNeeded = getTierMinQty(nextTier) - totalQuantity;
                return (
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 10, backgroundColor: "#FFFBEB", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 }}>
                    <Ionicons name="flash" size={14} color="#D97706" />
                    <Text fontSize={12} fontWeight="600" color="#92400E">
                      Add {moreNeeded} more for {"\u09F3"}{formatBDT(getTierPrice(nextTier), 0)}/pc pricing!
                    </Text>
                  </View>
                );
              }
              if (activeTier && totalQuantity > 0) {
                return (
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 10, backgroundColor: "#ECFDF5", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 }}>
                    <Ionicons name="checkmark-circle" size={14} color="#059669" />
                    <Text fontSize={12} fontWeight="600" color="#065F46">You're getting the best bulk price!</Text>
                  </View>
                );
              }
              return null;
            })()}
          </View>
        )}

        {/* ═══ VENDOR CARD ═══ */}
        {vendorName ? (
          <Pressable
            style={({ pressed }) => [s.card, pressed && { opacity: 0.7 }]}
            onPress={() => {
              const vendorSlug = product.vendor?.slug || product.vendor?.id;
              if (vendorSlug) {
                router.push({ pathname: "/supplier/[slug]", params: { slug: String(vendorSlug) } } as any);
              }
            }}
          >
            <View style={s.vendorRow}>
              <View style={s.vendorLogo}>
                <Text fontSize={13} fontWeight="800" color="#fff">
                  {vendorName.split(" ").map((w: string) => w[0]).join("").slice(0, 2).toUpperCase()}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text fontSize={10} color={GREY} style={{ textTransform: "uppercase", letterSpacing: 0.5 }}>Sold by</Text>
                <Text fontSize="$3" fontWeight="700" color={DARK}>{vendorName}</Text>
              </View>
              <View style={s.vendorArrow}>
                <Ionicons name="chevron-forward" size={14} color={GREY} />
              </View>
            </View>
          </Pressable>
        ) : null}

        {/* ═══ ORDERING SECTION ═══ */}
        {isResellerActive && (() => {
          // Determine if this is a "simple" product (no real variants, single Default size)
          const isSimpleProduct = variants.length <= 1 && sizesForTable.length === 1 && sizesForTable[0].size_name === "Default";

          if (isSimpleProduct) {
            // ── SIMPLE PRODUCT: Inline qty ──
            const sz = sizesForTable[0];
            const size = sz.size_name;
            const qty = variantQuantities[currentVarId]?.[size] || 0;
            const displayPrice = getSizePrice(sz, qty);


            return (
              <View style={s.card}>
                {/* Quantity Row */}
                <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between" }}>
                  <Text fontSize={15} fontWeight="700" color={DARK}>Quantity</Text>
                  <View style={{ flexDirection: "row", alignItems: "center", backgroundColor: "#F5F5FA", borderRadius: 14, padding: 4 }}>
                    <Pressable
                      onPress={() => handleQtyChange(currentVarId, size, "decrease", sz.qty)}
                      disabled={qty <= 0}
                      style={{
                        width: 40, height: 40, borderRadius: 10,
                        backgroundColor: qty <= 0 ? "#ECECEC" : "#fff",
                        justifyContent: "center", alignItems: "center",
                      }}
                    >
                      <Ionicons name="remove" size={18} color={qty <= 0 ? "#bbb" : DARK} />
                    </Pressable>
                    <TextInput
                      style={{
                        minWidth: 50, textAlign: "center",
                        fontSize: 20, fontWeight: "800" as any,
                        color: qty > 0 ? ACCENT : DARK,
                        paddingVertical: 4,
                      }}
                      keyboardType="number-pad"
                      value={String(qty)}
                      onChangeText={(v) => handleDirectQtyInput(currentVarId, size, v, sz.qty)}
                      selectTextOnFocus
                    />
                    <Pressable
                      onPress={() => handleQtyChange(currentVarId, size, "increase", sz.qty)}
                      disabled={qty >= sz.qty || sz.qty <= 0}
                      style={{
                        width: 40, height: 40, borderRadius: 10,
                        backgroundColor: (qty >= sz.qty || sz.qty <= 0) ? "#ECECEC" : "#fff",
                        justifyContent: "center", alignItems: "center",
                      }}
                    >
                      <Ionicons name="add" size={18} color={(qty >= sz.qty || sz.qty <= 0) ? "#bbb" : ACCENT} />
                    </Pressable>
                  </View>
                </View>

                {/* Stock info */}
                <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 8 }}>
                  <View style={{ width: 7, height: 7, borderRadius: 4, backgroundColor: sz.qty > 0 ? "#4CAF50" : "#E53935" }} />
                  <Text fontSize={12} color={GREY}>
                    {sz.qty > 0 ? `${sz.qty} in stock` : "Out of stock"}
                  </Text>
                  {minQty > 1 && <Text fontSize={11} color={GREY}>· Min order: {minQty}</Text>}
                </View>

                {/* Subtotal */}
                {qty > 0 && (
                  <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginTop: 16, paddingTop: 16, borderTopWidth: 1, borderTopColor: "#F0F0F5" }}>
                    <Text fontSize={14} color={GREY}>Subtotal</Text>
                    <Text fontSize={18} fontWeight="800" color={ACCENT}>৳{formatBDT(displayPrice * qty, 0)}</Text>
                  </View>
                )}


              </View>
            );
          }

          // MULTI-VARIANT / MULTI-SIZE: variant-first selector
          return (
            <View style={s.orderingSection}>
              <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Select Items</Text>

              <View style={s.variantPickerCard}>
                <View style={s.variantPickerHeader}>
                  <View>
                    <Text fontSize={14} fontWeight="800" color={DARK}>
                      Product Variants
                    </Text>
                    <Text fontSize={11} color={GREY}>
                      Tap a variant to choose size and quantity
                    </Text>
                  </View>
                  {totalQuantity > 0 && (
                    <View style={s.variantTotalBadge}>
                      <Text fontSize={11} fontWeight="800" color={ACCENT}>
                        {totalQuantity} pcs
                      </Text>
                    </View>
                  )}
                </View>

                <View style={s.variantSummaryRow}>
                  <Text style={s.variantSummaryLabel} fontSize={14} fontWeight="700" color={DARK}>
                    {variants.length > 1 ? "Color" : "Option"}
                  </Text>
                  <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={s.variantThumbList}
                  >
                    {normalizedVariants.map((variant) => {
                      const selectedVarQty = Object.values(variantQuantities[variant.variantId] ?? {})
                        .reduce((sum, qty) => sum + Number(qty || 0), 0);
                      const isCurrent = selectedVariant?.variantId === variant.variantId;

                      return (
                        <Pressable
                          key={variant.variantId}
                          onPress={() => openVariantSheet(variant.variantId)}
                          style={[
                            s.variantThumbButton,
                            isCurrent && s.variantThumbButtonActive,
                          ]}
                        >
                          {selectedVarQty > 0 && (
                            <View style={s.variantQtyBadge}>
                              <Text fontSize={9} fontWeight="800" color="#fff">
                                {selectedVarQty}
                              </Text>
                            </View>
                          )}
                          {variant.image ? (
                            <Image source={{ uri: variant.image }} style={s.variantThumbImage} resizeMode="cover" />
                          ) : variant.colorCode ? (
                            <View style={[s.variantThumbSwatch, { backgroundColor: variant.colorCode }]} />
                          ) : (
                            <View style={s.variantThumbFallback}>
                              <Text fontSize={11} fontWeight="800" color={GREY}>
                                {variant.label.slice(0, 2).toUpperCase()}
                              </Text>
                            </View>
                          )}
                        </Pressable>
                      );
                    })}
                  </ScrollView>
                  <Pressable
                    onPress={() => openVariantSheet(selectedVariant.variantId)}
                    style={s.variantOpenButton}
                  >
                    <Ionicons name="chevron-forward" size={22} color={DARK} />
                  </Pressable>
                </View>

                <View style={s.variantSelectionSummary}>
                  {selectedVariantSummary.length > 0 ? (
                    selectedVariantSummary.slice(0, 3).map((item) => (
                      <View key={item} style={s.selectedSizeChip}>
                        <Text fontSize={11} fontWeight="700" color={DARK} numberOfLines={1}>
                          {item}
                        </Text>
                      </View>
                    ))
                  ) : (
                    <Text fontSize={12} color={GREY}>
                      No variants selected yet
                    </Text>
                  )}
                  {selectedVariantSummary.length > 3 && (
                    <Text fontSize={11} fontWeight="700" color={ACCENT}>
                      +{selectedVariantSummary.length - 3} more
                    </Text>
                  )}
                </View>
              </View>
            </View>
          );
        })()}

        {/* ═══ TOTAL SELLING PRICE (Dropshipping) ═══ */}
        {showDropshipping && isResellerActive && (() => {
          const allItems = getSelectedItems();
          const totalCostPrice = allItems.reduce((sum, item) => sum + item.price * item.qty, 0);

          const fallbackVarId = variants.length > 0 ? (variants[0]?.id ?? 0) : 0;
          const fallbackSizes = variants.length > 0 && variants[0]?.sizes?.length > 0
            ? variants[0].sizes.map((sz: any) => sz.size_name)
            : sizesForTable.map((sz) => sz.size_name);
          const singleSP = variantSellingPrices[fallbackVarId]?.[fallbackSizes[0]] || "";
          const spNum = singleSP ? parseFloat(singleSP) : 0;

          const minTotalSellingPrice = totalCostPrice;
          const isTooLow = singleSP !== "" && spNum < minTotalSellingPrice;
          const totalEarnings = spNum > 0 && spNum >= totalCostPrice ? spNum - totalCostPrice : 0;

          const handleTotalSPChange = (val: string) => {
            setVariantSellingPrices((prev) => {
              const next = { ...prev };
              if (variants.length > 0) {
                for (const v of variants) {
                  const sizes = v.sizes?.length > 0 ? v.sizes.map((sz: any) => sz.size_name) : fallbackSizes;
                  const varSizes: Record<string, string> = {};
                  for (const sz of sizes) {
                    varSizes[sz] = val;
                  }
                  next[v.id] = varSizes;
                }
              } else {
                const varSizes: Record<string, string> = {};
                for (const sz of fallbackSizes) {
                  varSizes[sz] = val;
                }
                next[fallbackVarId] = varSizes;
              }
              return next;
            });
          };

          return (
            <View style={s.card}>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 10 }}>
                <Ionicons name="pricetag" size={14} color={ACCENT} />
                <Text fontSize={14} fontWeight="700" color={DARK}>Your total selling price</Text>
              </View>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 10 }}>
                <Text fontSize={16} fontWeight="600" color={GREY}>{"\u09F3"}</Text>
                <TextInput
                  style={[
                    {
                      flex: 1, height: 48, borderRadius: 12, borderWidth: 1.5, borderColor: "#E5E5EA",
                      fontSize: 18, fontWeight: "700" as any, backgroundColor: "#FAFAFA",
                      paddingHorizontal: 14, color: DARK,
                    },
                    isTooLow && { borderColor: "#EF4444", backgroundColor: "#FEF2F2" },
                    spNum >= minTotalSellingPrice && singleSP ? { borderColor: "#059669", backgroundColor: "#ECFDF5" } : {},
                    totalQuantity === 0 && { backgroundColor: "#F0F0F5" },
                  ]}
                  keyboardType="numeric"
                  value={singleSP}
                  onChangeText={handleTotalSPChange}
                  placeholder={totalQuantity > 0 ? `Enter total selling price (\u2265\u09F3${formatBDT(minTotalSellingPrice, 0)})` : "Select items first"}
                  placeholderTextColor="#bbb"
                  editable={totalQuantity > 0}
                />
              </View>
              {isTooLow && (
                <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 10, backgroundColor: "#FEF2F2", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 }}>
                  <Ionicons name="alert-circle" size={16} color="#EF4444" />
                  <Text fontSize={13} fontWeight="700" color="#EF4444">Total selling price must be at least {"\u09F3"}{formatBDT(minTotalSellingPrice, 0)}</Text>
                </View>
              )}
              {totalEarnings > 0 && totalQuantity > 0 && (
                <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 10, backgroundColor: "#ECFDF5", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 }}>
                  <Ionicons name="trending-up" size={16} color="#059669" />
                  <Text fontSize={14} fontWeight="800" color="#059669">
                    Your total earnings: +{"\u09F3"}{formatBDT(totalEarnings, 0)}
                  </Text>
                </View>
              )}
            </View>
          );
        })()}

        {/* ═══ YOUTUBE ═══ */}
        {youtubeLink ? (
          <Pressable style={s.youtubeCard} onPress={() => Linking.openURL(youtubeLink)}>
            <View style={s.ytIcon}><Ionicons name="play" size={18} color="#fff" /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Watch Product Video</Text>
              <Text fontSize={11} color={GREY}>See it in action on YouTube</Text>
            </View>
            <Ionicons name="open-outline" size={16} color={ACCENT} />
          </Pressable>
        ) : null}

        {/* ═══ DESCRIPTION / SPECS TABS ═══ */}
        <View style={s.card}>
          <View style={s.tabBar}>
            <Pressable style={[s.tab, activeTab === "desc" && s.tabActive]} onPress={() => setActiveTab("desc")}>
              <Text fontSize="$3" fontWeight="600" color={activeTab === "desc" ? ACCENT : GREY}>Description</Text>
            </Pressable>
            <Pressable style={[s.tab, activeTab === "specs" && s.tabActive]} onPress={() => setActiveTab("specs")}>
              <Text fontSize="$3" fontWeight="600" color={activeTab === "specs" ? ACCENT : GREY}>Specifications</Text>
            </Pressable>
          </View>

          {activeTab === "desc" && hasDescription ? (
            <View style={{ marginTop: 12 }}>
              <ProductDescriptionBlocks blocks={descriptionPreview.blocks} />
              {showDescActions ? (
                <View style={s.descActions}>
                  {showReadMore ? (
                    <Pressable onPress={() => setDescExpanded(p => !p)} style={s.readMoreBtn}>
                      <Text fontSize="$2" fontWeight="600" color={ACCENT}>
                        {descExpanded ? "Show less" : "Read more"}
                      </Text>
                      <Ionicons name={descExpanded ? "chevron-up" : "chevron-down"} size={14} color={ACCENT} />
                    </Pressable>
                  ) : null}
                  {showCopyDescription ? (
                    <Pressable
                      onPress={handleCopyDescription}
                      style={({ pressed }) => [s.copyDescBtn, pressed && { opacity: 0.7 }]}
                    >
                      <Ionicons name="copy-outline" size={14} color={ACCENT} />
                      <Text fontSize="$2" fontWeight="600" color={ACCENT}>Copy Description</Text>
                    </Pressable>
                  ) : null}
                </View>
              ) : null}
            </View>
          ) : null}

          {activeTab === "specs" && (
            <View style={{ marginTop: 12 }}>
              {[
                product.color && ["Color", product.color],
                product.size && ["Size", typeof product.size === "string" ? product.size : JSON.stringify(product.size)],
                (product.weight || product.product_weight) && ["Weight", product.weight ?? product.product_weight],
                sku && ["SKU", sku],
                categoryName && ["Category", categoryName],
                product.minimum_qty && ["Min. Order", String(product.minimum_qty)],
                product.shipping_days && ["Delivery", `${product.shipping_days} days`],
              ].filter(Boolean).map(([label, value], i) => (
                <View key={i} style={s.specRow}>
                  <Text style={s.specLabel}>{label as string}</Text>
                  <Text style={s.specValue}>{value as string}</Text>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* ═══ DELIVERY INFO ═══ */}
        <View style={s.card}>
          <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Delivery & Returns</Text>
          <View style={s.deliveryRow}>
            <View style={s.deliveryIcon}><Ionicons name="cube-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Standard Delivery</Text>
              <Text fontSize={11} color={GREY}>{product.shipping_days ? `Estimated ${product.shipping_days} days` : "Delivery time varies"}</Text>
            </View>
          </View>
          <View style={s.deliveryRow}>
            <View style={s.deliveryIcon}><Ionicons name="refresh-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Easy Returns</Text>
              <Text fontSize={11} color={GREY}>Hassle-free return policy</Text>
            </View>
          </View>
          <View style={[s.deliveryRow, { borderBottomWidth: 0 }]}>
            <View style={s.deliveryIcon}><Ionicons name="shield-checkmark-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Secure Payment</Text>
              <Text fontSize={11} color={GREY}>Your payment is safe with us</Text>
            </View>
          </View>
        </View>

        {/* ═══ REVIEWS & RATINGS ═══ */}
        {productId && <ReviewsSection productId={productId} isLoggedIn={isLoggedIn} />}

        {/* ═══ RELATED PRODUCTS ═══ */}
        {relatedProducts.length > 0 && (
          <View style={{ paddingTop: 16 }}>
            <Text fontSize="$5" fontWeight="700" color={DARK} ml={20} mb="$3">You May Also Like</Text>
            <FlatList
              data={relatedProducts.slice(0, 10)} horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ paddingHorizontal: 20, gap: 12 }}
              keyExtractor={(item: any) => String(item.id)}
              renderItem={({ item }: any) => (
                <ProductCard
                  name={item.ProductName}
                  price={String(item.storefront_price ?? item.ProductSalePrice ?? item.ProductRegularPrice)}
                  image={item.ViewProductImage}
                  slug={item.ProductSlug}
                  variant="horizontal"
                />
              )}
            />
          </View>
        )}
      </KeyboardAwareScrollView>

      {/* ═══ ANIMATED FIXED HEADER (fades in on scroll) ═══ */}
      <Animated.View
        style={[s.fixedHeader, { paddingTop: insets.top, backgroundColor: headerBg, borderBottomWidth: 1, borderBottomColor: headerBorder }]}
        pointerEvents="box-none"
      >
        <Animated.View style={[s.fixedHeaderInner, { opacity: headerTitleOpacity }]} pointerEvents="auto">
          <Pressable style={s.fixedHeaderBtn} onPress={() => router.back()} hitSlop={8}>
            <Ionicons name="arrow-back" size={22} color={DARK} />
          </Pressable>
          <View style={{ flex: 1, marginHorizontal: 12 }}>
            <Text fontSize={15} fontWeight="700" color={DARK} numberOfLines={1}>
              {product?.ProductName ?? ""}
            </Text>
          </View>
          <View style={{ flexDirection: "row", alignItems: "center", gap: 4 }}>
            <Pressable
              style={[s.fixedHeaderBtn, isInShop && s.shopActiveBtn]}
              onPress={handleToggleShop}
              disabled={isShopActionBusy}
              hitSlop={8}
            >
              {isShopActionBusy ? (
                <ActivityIndicator size="small" color={isInShop ? "#059669" : DARK} />
              ) : (
                <Ionicons name={isInShop ? "storefront" : "storefront-outline"} size={20} color={isInShop ? "#059669" : DARK} />
              )}
            </Pressable>
            <Pressable style={s.fixedHeaderBtn} onPress={handleShareProduct} hitSlop={8}>
              <Ionicons name="share-social-outline" size={20} color={DARK} />
            </Pressable>
          </View>
        </Animated.View>
      </Animated.View>

      {/* ═══ STICKY BOTTOM BAR ═══ */}
      <View style={[s.bottomBar, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        {isResellerActive ? (
          <>
            <Pressable
              style={({ pressed }) => [s.cartBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={handleAddToCart}
              disabled={isBusy || addToCartMutation.isPending || totalQuantity === 0}
            >
              {addToCartMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <Ionicons name="cart-outline" size={18} color="#fff" />
                  <Text fontSize="$3" fontWeight="700" color="#fff">
                    {totalQuantity > 0 ? `Add (${totalQuantity})` : "Add to Cart"}
                  </Text>
                </>
              )}
            </Pressable>
            <Pressable
              style={({ pressed }) => [s.buyBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={handleBuyNow}
              disabled={isBusy || addToCartMutation.isPending || totalQuantity === 0}
            >
              <Text fontSize="$3" fontWeight="700" color="#fff">Buy Now</Text>
            </Pressable>
          </>
        ) : (
          <Pressable
            style={({ pressed }) => [s.lockedBtn, pressed && { opacity: 0.85 }]}
            onPress={() => {
              if (!isLoggedIn) {
                router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
              } else {
                setShowActivation(true);
              }
            }}
          >
            <Ionicons name="lock-closed" size={16} color="#fff" />
            <Text fontSize="$3" fontWeight="700" color="#fff">
              {isLoggedIn ? "Activate Profile to Order" : "Login to Order"}
            </Text>
          </Pressable>
        )}
      </View>

      {/* Activation Bottom Sheet Modal */}
      <Modal
        visible={showActivation}
        transparent
        statusBarTranslucent
        animationType="fade"
        onRequestClose={() => setShowActivation(false)}
      >
        <Pressable style={s.sheetOverlay} onPress={() => setShowActivation(false)}>
          <Animated.View style={[s.sheetContainer, { transform: [{ translateY: sheetAnim }] }]}>
            <View style={s.sheetHandle} />
            <View style={s.sheetIconWrap}>
              <Ionicons name="shield-checkmark" size={36} color={ACCENT} />
            </View>
            <Text fontSize="$6" fontWeight="bold" color={DARK} textAlign="center" mt="$3">
              Activate Your Profile
            </Text>
            <Text fontSize="$3" color={GREY} textAlign="center" mt="$2" lineHeight={20} mx="$2">
              You need an active reseller profile to view prices and place orders.{"\n"}
              Upgrade your membership to unlock wholesale pricing, place bulk orders, and access exclusive deals.
            </Text>
            <View style={s.sheetBenefits}>
              {[
                { icon: "pricetag", text: "View wholesale prices" },
                { icon: "cart", text: "Place orders & buy products" },
                { icon: "flash", text: "Access flash deals & discounts" },
              ].map((b, i) => (
                <View key={i} style={s.sheetBenefitRow}>
                  <Ionicons name={b.icon as any} size={18} color={ACCENT} />
                  <Text fontSize="$3" color={DARK} ml="$2">{b.text}</Text>
                </View>
              ))}
            </View>
            <Pressable
              style={({ pressed }) => [s.sheetCta, pressed && { opacity: 0.85 }]}
              onPress={() => {
                setShowActivation(false);
                router.push(subscriptionDestination as any);
              }}
            >
              <Ionicons name="rocket" size={18} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff" ml="$2">Activate Now</Text>
            </Pressable>
            <Pressable onPress={() => setShowActivation(false)} style={{ marginTop: 12 }}>
              <Text fontSize="$3" color={GREY} textAlign="center">Maybe later</Text>
            </Pressable>
          </Animated.View>
        </Pressable>
      </Modal>

      {/* VARIANT SELECTION BOTTOM SHEET */}
      <Sheet
        modal
        open={selectedVariantId !== null}
        onOpenChange={(open: boolean) => { if (!open) closeVariantSheet(); }}
        snapPoints={[90]}
        dismissOnSnapToBottom
        animation="quick"
        zIndex={100_000}
      >
        <Sheet.Overlay
          animation="lazy"
          enterStyle={{ opacity: 0 }}
          exitStyle={{ opacity: 0 }}
          backgroundColor="rgba(0,0,0,0.5)"
        />
        <Sheet.Handle />
        <Sheet.Frame
          backgroundColor="#fff"
          borderTopLeftRadius={28}
          borderTopRightRadius={28}
        >

            <View style={s.variantSheetHeader}>
              <View style={{ flex: 1 }}>
                <Text fontSize={18} fontWeight="800" color={DARK} letterSpacing={-0.3}>Select Variants</Text>
                <Text fontSize={12} color={GREY} mt="$1">
                  {normalizedVariants.length} variant{normalizedVariants.length !== 1 ? "s" : ""} · {selectedVariant.sizes.length} size{selectedVariant.sizes.length !== 1 ? "s" : ""}
                </Text>
              </View>
              <Pressable onPress={closeVariantSheet} style={s.variantSheetCloseBtn}>
                <Ionicons name="close" size={18} color="#666" />
              </Pressable>
            </View>

            {supportsWholesale && isResellerActive && selectedSheetBulkSize && (
              <View style={s.sheetTierPanel}>
                <View style={s.sheetTierTitleRow}>
                  <Ionicons name="pricetag-outline" size={14} color={ACCENT} />
                  <Text fontSize={12} fontWeight="800" color={ACCENT} style={{ textTransform: "uppercase" }}>
                    Bulk discounts for size {selectedSheetBulkSize.sizeName}
                  </Text>
                  {selectedSheetBulkQty > 0 && (
                    <View style={s.sheetTierQtyBadge}>
                      <Text fontSize={10} fontWeight="800" color={ACCENT}>{selectedSheetBulkQty} pcs</Text>
                    </View>
                  )}
                </View>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                  {sortTiersByQty(selectedSheetBulkSize.bulkPrices).map((tier: any, tierIdx: number) => {
                    const isActive = isSameTier(selectedSheetBulkTier, tier);
                    return (
                      <View key={tier.id ?? tierIdx} style={[s.sheetTierBadge, isActive && s.sheetTierBadgeActive]}>
                        <Text fontSize={11} fontWeight="800" color={isActive ? ACCENT : DARK}>
                          {getTierQtyLabel(tier)}
                        </Text>
                        <Text fontSize={13} fontWeight="900" color={isActive ? ACCENT : DARK}>
                          {"\u09F3"}{formatBDT(getTierPrice(tier), 0)}
                        </Text>
                      </View>
                    );
                  })}
                </ScrollView>
                {selectedSheetBulkTier && selectedSheetBulkQty > 0 && (
                  <View style={s.sheetTierMessage}>
                    <Ionicons name="checkmark-circle" size={14} color="#059669" />
                    <Text fontSize={12} fontWeight="700" color="#065F46">
                      Size discount applied to the unit price below
                    </Text>
                  </View>
                )}
              </View>
            )}

            {showWholesale && isResellerActive && priceTiers.length > 0 && (
              <View style={s.sheetTierPanel}>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                  {sortTiersByQty(priceTiers).map((tier: any, tierIdx: number) => {
                    const isActive = isSameTier(activeTier, tier);
                    const qtyLabel = getTierQtyLabel(tier);
                    const tierPrice = getTierPrice(tier);
                    return (
                      <View key={tier.id ?? tierIdx} style={[s.sheetTierBadge, isActive && s.sheetTierBadgeActive]}>
                        <Text fontSize={15} fontWeight="800" color={isActive ? ACCENT : DARK}>
                          {"\u09F3"}{formatBDT(tierPrice, 0)}
                        </Text>
                        <Text fontSize={10} fontWeight="600" color={isActive ? ACCENT : GREY}>{qtyLabel}</Text>
                      </View>
                    );
                  })}
                </ScrollView>
                {activeTier && totalQuantity > 0 && (
                  <View style={s.sheetTierMessage}>
                    <Ionicons name="checkmark-circle" size={14} color="#059669" />
                    <Text fontSize={12} fontWeight="700" color="#065F46">
                      Current bulk price applied across selected variants
                    </Text>
                  </View>
                )}
              </View>
            )}

            <View style={s.sheetVariantPanel}>
              <Text fontSize={14} fontWeight="800" color={DARK}>
                {variants.length > 1 ? "Color" : "Option"}: {selectedVariant.label}
              </Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={s.sheetVariantThumbList}>
                {normalizedVariants.map((variant) => {
                  const isActive = variant.variantId === selectedVariant.variantId;
                  const selectedVarQty = Object.values(variantQuantities[variant.variantId] ?? {})
                    .reduce((sum, qty) => sum + Number(qty || 0), 0);
                  return (
                    <Pressable
                      key={variant.variantId}
                      onPress={() => openVariantSheet(variant.variantId)}
                      style={[s.sheetVariantThumb, isActive && s.sheetVariantThumbActive]}
                    >
                      {selectedVarQty > 0 && (
                        <View style={s.variantQtyBadge}>
                          <Text fontSize={9} fontWeight="800" color="#fff">{selectedVarQty}</Text>
                        </View>
                      )}
                      {variant.image ? (
                        <Image source={{ uri: variant.image }} style={s.sheetVariantThumbImage} resizeMode="cover" />
                      ) : variant.colorCode ? (
                        <View style={[s.sheetVariantSwatch, { backgroundColor: variant.colorCode }]} />
                      ) : (
                        <View style={s.sheetVariantFallback}>
                          <Text fontSize={11} fontWeight="800" color={GREY}>
                            {variant.label.slice(0, 2).toUpperCase()}
                          </Text>
                        </View>
                      )}
                    </Pressable>
                  );
                })}
              </ScrollView>
            </View>

            <View style={s.sheetSizeHeader}>
              <Text style={{ flex: 1.2 }} fontSize={11} fontWeight="700" color={GREY} letterSpacing={0.5} textTransform="uppercase">Size</Text>
              <Text style={{ flex: 1 }} fontSize={11} fontWeight="700" color={GREY} letterSpacing={0.5} textTransform="uppercase">Price</Text>
              <Text style={{ flex: 1.2, textAlign: "right" }} fontSize={11} fontWeight="700" color={GREY} letterSpacing={0.5} textTransform="uppercase">Quantity</Text>
            </View>

            <KeyboardAwareScrollView
              style={{ flexGrow: 1, flexShrink: 1, minHeight: 120 }}
              contentContainerStyle={{ paddingBottom: 14 }}
              keyboardShouldPersistTaps="handled"
              showsVerticalScrollIndicator={false}
              bottomOffset={Math.max(insets.bottom, 16) + 80}
              keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
            >
              {selectedVariant.sizes.map((sizeItem) => {
                const size = sizeItem.sizeName;
                const qty = variantQuantities[selectedVariant.variantId]?.[size] || 0;
                const unitPrice = getSizePrice(
                  {
                    price: sizeItem.sizePrice,
                    qty: sizeItem.sizeStock,
                    bulk_prices: sizeItem.bulkPrices,
                  },
                  qty,
                );
                const rowBulkTier = findApplicableTier(sizeItem.bulkPrices, qty);
                const rowKey = String(selectedVariant.variantId) + "-" + size;

                return (
                  <View
                    key={rowKey}
                    style={[s.sheetSizeRowWrap, qty > 0 && s.sheetSizeRowWrapActive]}
                  >
                    {qty > 0 && <View style={s.sheetSizeRowAccent} />}
                    <View style={s.sheetSizeRow}>
                      <View style={{ flex: 1.2 }}>
                        <Text fontSize={15} fontWeight="700" color={DARK} numberOfLines={2}>
                          {size === "Default" ? "Std" : size}
                        </Text>
                        <Text fontSize={10} color={sizeItem.sizeStock <= 0 ? "#EF4444" : GREY} mt="$0.5">
                          {sizeItem.sizeStock <= 0 ? "Out of stock" : String(sizeItem.sizeStock) + " in stock"}
                        </Text>
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text fontSize={14} fontWeight="800" color={DARK}>{"\u09F3"}{formatBDT(unitPrice, 0)}</Text>
                        {sizeItem.bulkPrices.length > 0 && (
                          <View style={{ flexDirection: "row", alignItems: "center", gap: 3, marginTop: 2 }}>
                            <View style={{ width: 4, height: 4, borderRadius: 2, backgroundColor: rowBulkTier ? ACCENT : "#059669" }} />
                            <Text fontSize={9} fontWeight="800" color={rowBulkTier ? ACCENT : "#059669"}>
                              {rowBulkTier ? getTierQtyLabel(rowBulkTier).toUpperCase() : "BULK"}
                            </Text>
                          </View>
                        )}
                      </View>
                      <View style={s.sheetStepperWrap}>
                        <Pressable
                          style={[s.sheetStepBtn, qty <= 0 && s.miniStepBtnDisabled]}
                          onPress={() => handleQtyChange(selectedVariant.variantId, size, "decrease", sizeItem.sizeStock)}
                          disabled={qty <= 0}
                        >
                          <Ionicons name="remove" size={16} color={qty <= 0 ? "#ccc" : DARK} />
                        </Pressable>
                        <View style={s.sheetStepVal}>
                          <TextInput
                            style={{
                              fontSize: 15, fontWeight: "800" as any,
                              color: qty > 0 ? ACCENT : DARK,
                              textAlign: "center",
                              minWidth: 36,
                              paddingVertical: 2,
                              paddingHorizontal: 0,
                            }}
                            keyboardType="number-pad"
                            value={String(qty)}
                            onChangeText={(v) => handleDirectQtyInput(selectedVariant.variantId, size, v, sizeItem.sizeStock)}
                            selectTextOnFocus
                          />
                        </View>
                        <Pressable
                          style={[s.sheetStepBtn, (qty >= sizeItem.sizeStock || sizeItem.sizeStock <= 0) && s.miniStepBtnDisabled]}
                          onPress={() => handleQtyChange(selectedVariant.variantId, size, "increase", sizeItem.sizeStock)}
                          disabled={qty >= sizeItem.sizeStock || sizeItem.sizeStock <= 0}
                        >
                          <Ionicons name="add" size={16} color={(qty >= sizeItem.sizeStock || sizeItem.sizeStock <= 0) ? "#ccc" : ACCENT} />
                        </Pressable>
                      </View>
                    </View>
                  </View>
                );
              })}
            </KeyboardAwareScrollView>

            <View
              style={[s.variantSheetFooter, { paddingBottom: Math.max(insets.bottom, 16) + 4 }]}
            >
              {/* ── Summary Card ── */}
              <View style={s.sheetSummaryCard}>
                <View style={s.sheetTotalRow}>
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
                    <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: totalQuantity > 0 ? ACCENT : "#ccc" }} />
                    <Text fontSize={13} fontWeight="600" color={GREY}>Total items: {totalQuantity} Pieces</Text>
                  </View>
                  <Text fontSize={16} fontWeight="800" color={DARK} letterSpacing={-0.3}>{"\u09F3"}{formatBDT(selectedItemsTotal, 0)}</Text>
                </View>
              </View>

              {/* ── Total Selling Price Input in Sheet ── */}
              {showDropshipping && totalQuantity > 0 && (() => {
                const fallbackVarId = variants.length > 0 ? (variants[0]?.id ?? 0) : 0;
                const fallbackSizes = variants.length > 0 && variants[0]?.sizes?.length > 0
                  ? variants[0].sizes.map((sz: any) => sz.size_name)
                  : sizesForTable.map((sz) => sz.size_name);
                const singleSP = variantSellingPrices[fallbackVarId]?.[fallbackSizes[0]] || "";
                const spNum = singleSP ? parseFloat(singleSP) : 0;
                const isTooLow = singleSP !== "" && spNum < selectedItemsTotal;
                const totalEarnings = spNum > 0 && spNum >= selectedItemsTotal ? spNum - selectedItemsTotal : 0;

                const handleSheetSPChange = (val: string) => {
                  setVariantSellingPrices((prev) => {
                    const next = { ...prev };
                    if (variants.length > 0) {
                      for (const v of variants) {
                        const sizes = v.sizes?.length > 0 ? v.sizes.map((sz: any) => sz.size_name) : fallbackSizes;
                        const varSizes: Record<string, string> = {};
                        for (const sz of sizes) { varSizes[sz] = val; }
                        next[v.id] = varSizes;
                      }
                    } else {
                      const varSizes: Record<string, string> = {};
                      for (const sz of fallbackSizes) { varSizes[sz] = val; }
                      next[fallbackVarId] = varSizes;
                    }
                    return next;
                  });
                };

                return (
                  <View style={s.sheetSellingPriceSection}>
                    <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 8 }}>
                      <Ionicons name="pricetag" size={13} color={ACCENT} />
                      <Text fontSize={13} fontWeight="700" color={DARK}>Your total selling price</Text>
                    </View>
                    <View style={{ flexDirection: "row", alignItems: "center", gap: 10 }}>
                      <View style={s.sheetCurrencyBadge}>
                        <Text fontSize={15} fontWeight="700" color={ACCENT}>{"\u09F3"}</Text>
                      </View>
                      <TextInput
                        style={[
                          s.sheetSellingPriceInput,
                          isTooLow && { borderColor: "#EF4444", backgroundColor: "#FEF2F2" },
                          spNum >= selectedItemsTotal && singleSP ? { borderColor: "#059669", backgroundColor: "#F0FDF9" } : {},
                        ]}
                        keyboardType="numeric"
                        value={singleSP}
                        onChangeText={handleSheetSPChange}
                        placeholder={`\u2265\u09F3${formatBDT(selectedItemsTotal, 0)}`}
                        placeholderTextColor="#bbb"
                      />
                      {totalEarnings > 0 ? (
                        <View style={s.sheetEarningsBadge}>
                          <Ionicons name="trending-up" size={13} color="#059669" />
                          <Text fontSize={12} fontWeight="800" color="#059669">+{"\u09F3"}{formatBDT(totalEarnings, 0)}</Text>
                        </View>
                      ) : isTooLow ? (
                        <View style={s.sheetEarningsBadgeError}>
                          <Ionicons name="alert-circle" size={13} color="#EF4444" />
                          <Text fontSize={11} fontWeight="700" color="#EF4444">Too low</Text>
                        </View>
                      ) : null}
                    </View>
                  </View>
                );
              })()}

              {/* ── Action Buttons ── */}
              <View style={s.sheetActionRow}>
                <Pressable
                  onPress={handleBuyNow}
                  disabled={isBusy || addToCartMutation.isPending || totalQuantity === 0}
                  style={({ pressed }) => [
                    s.sheetBuyBtn,
                    (isBusy || addToCartMutation.isPending || totalQuantity === 0) && { opacity: 0.5 },
                    pressed && !(isBusy || addToCartMutation.isPending || totalQuantity === 0) && { opacity: 0.85, transform: [{ scale: 0.98 }] },
                  ]}
                >
                  <Text fontSize={15} fontWeight="800" color="#fff">Buy Now</Text>
                </Pressable>
                <Pressable
                  onPress={handleAddToCart}
                  disabled={isBusy || addToCartMutation.isPending || totalQuantity === 0}
                  style={({ pressed }) => [
                    s.sheetCartBtn,
                    (isBusy || addToCartMutation.isPending || totalQuantity === 0) && { opacity: 0.5 },
                    pressed && !(isBusy || addToCartMutation.isPending || totalQuantity === 0) && { opacity: 0.85, transform: [{ scale: 0.98 }] },
                  ]}
                >
                  {addToCartMutation.isPending ? (
                    <ActivityIndicator size="small" color="#fff" />
                  ) : (
                    <Text fontSize={15} fontWeight="800" color="#fff">Add to Cart</Text>
                  )}
                </Pressable>
              </View>
            </View>
        </Sheet.Frame>
      </Sheet>
      <AppDialog state={dialog} onClose={closeDialog} />
    </View>
  );
}

/* ═══════════════════════════════════════════════════════════════
   REVIEWS & RATINGS SECTION
   ═══════════════════════════════════════════════════════════════ */
function StarRating({ rating, size = 14 }: { rating: number; size?: number }) {
  return (
    <View style={{ flexDirection: "row", gap: 2 }}>
      {[1, 2, 3, 4, 5].map((v) => (
        <Ionicons key={v} name={v <= rating ? "star" : "star-outline"} size={size} color={v <= rating ? "#F59E0B" : "#D1D5DB"} />
      ))}
    </View>
  );
}

function ReviewsSection({ productId, isLoggedIn }: { productId: number; isLoggedIn: boolean }) {
  const [showWriteReview, setShowWriteReview] = useState(false);
  const [editingReview, setEditingReview] = useState(false);
  const [rating, setRating] = useState(0);
  const [comment, setComment] = useState("");
  const queryClient = useQueryClient();

  const reviewsQuery = useQuery({
    queryKey: ["product-reviews", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/review/product/${productId}`);
      return data?.data ?? data;
    },
    enabled: !!productId,
  });

  const checkReviewQuery = useQuery({
    queryKey: ["check-review", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/review/check/${productId}`);
      return data?.data ?? data;
    },
    enabled: !!productId && isLoggedIn,
  });

  const submitMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await apiClient.post("/review/store", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["product-reviews", productId] });
      queryClient.invalidateQueries({ queryKey: ["check-review", productId] });
      toast.success("Review submitted!");
      setShowWriteReview(false);
      setRating(0);
      setComment("");
    },
    onError: () => toast.error("Failed to submit review"),
  });

  const updateMutation = useMutation({
    mutationFn: async ({ reviewId, formData }: { reviewId: number; formData: FormData }) => {
      const { data } = await apiClient.post(`/review/update/${reviewId}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["product-reviews", productId] });
      queryClient.invalidateQueries({ queryKey: ["check-review", productId] });
      toast.success("Review updated!");
      setEditingReview(false);
    },
    onError: () => toast.error("Failed to update review"),
  });

  const reviews = reviewsQuery.data?.reviews ?? [];
  const reviewCount = reviewsQuery.data?.review_count ?? 0;
  const averageRating = Number(reviewsQuery.data?.average_rating ?? 0);
  const canReview = checkReviewQuery.data?.can_review ?? false;
  const hasReviewed = checkReviewQuery.data?.has_reviewed ?? false;
  const existingReview = checkReviewQuery.data?.review ?? null;

  const handleSubmit = () => {
    if (rating === 0) { toast.error("Please select a rating"); return; }
    const formData = new FormData();
    formData.append("product_id", String(productId));
    formData.append("rating", String(rating));
    if (comment.trim()) formData.append("messages", comment.trim());
    submitMutation.mutate(formData);
  };

  const handleUpdate = () => {
    if (!existingReview || rating === 0) return;
    const formData = new FormData();
    formData.append("rating", String(rating));
    if (comment.trim()) formData.append("messages", comment.trim());
    updateMutation.mutate({ reviewId: existingReview.id, formData });
  };

  return (
    <View style={[s.card, { marginTop: 10 }]}>
      <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginBottom: 12 }}>
        <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
          <Ionicons name="star" size={16} color="#F59E0B" />
          <Text fontSize={14} fontWeight="700" color={DARK}>Reviews & Ratings</Text>
        </View>
        {reviewCount > 0 && (
          <View style={{ flexDirection: "row", alignItems: "center", gap: 4 }}>
            <Text fontSize={20} fontWeight="800" color={DARK}>{averageRating.toFixed(1)}</Text>
            <Text fontSize={12} color={GREY}>({reviewCount})</Text>
          </View>
        )}
      </View>

      {/* Rating distribution */}
      {reviewCount > 0 && (
        <View style={{ marginBottom: 16, backgroundColor: "#FAFAFA", borderRadius: 12, padding: 12 }}>
          {[5, 4, 3, 2, 1].map((star) => {
            const count = reviews.filter((r: any) => Math.round(r.rating) === star).length;
            const pct = reviewCount > 0 ? (count / reviewCount) * 100 : 0;
            return (
              <View key={star} style={{ flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 4 }}>
                <Text fontSize={11} color={GREY} style={{ width: 12 }}>{star}</Text>
                <Ionicons name="star" size={10} color="#F59E0B" />
                <View style={{ flex: 1, height: 6, borderRadius: 3, backgroundColor: "#E5E7EB" }}>
                  <View style={{ width: `${pct}%`, height: 6, borderRadius: 3, backgroundColor: "#F59E0B" } as any} />
                </View>
                <Text fontSize={10} color={GREY} style={{ width: 20, textAlign: "right" }}>{count}</Text>
              </View>
            );
          })}
        </View>
      )}

      {/* User's existing review */}
      {hasReviewed && existingReview && !editingReview && (
        <View style={{ backgroundColor: "#ECFDF5", borderRadius: 12, padding: 12, marginBottom: 12, flexDirection: "row", alignItems: "center", gap: 10 }}>
          <Ionicons name="checkmark-circle" size={18} color="#059669" />
          <View style={{ flex: 1 }}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
              <Text fontSize={12} fontWeight="600" color="#065F46">Your review</Text>
              <StarRating rating={existingReview.rating} size={12} />
            </View>
            {existingReview.messages && <Text fontSize={11} color="#065F46" numberOfLines={1} mt="$1">{existingReview.messages}</Text>}
          </View>
          <Pressable onPress={() => { setEditingReview(true); setRating(existingReview.rating); setComment(existingReview.messages ?? ""); }}>
            <Text fontSize={12} fontWeight="700" color="#059669">Edit</Text>
          </Pressable>
        </View>
      )}

      {/* Edit review form */}
      {editingReview && existingReview && (
        <View style={{ backgroundColor: "#F0F9FF", borderRadius: 12, padding: 14, marginBottom: 12 }}>
          <Text fontSize={13} fontWeight="700" color={DARK} mb="$2">Edit Your Review</Text>
          <View style={{ flexDirection: "row", gap: 6, marginBottom: 10 }}>
            {[1, 2, 3, 4, 5].map((v) => (
              <Pressable key={v} onPress={() => setRating(v)}>
                <Ionicons name={v <= rating ? "star" : "star-outline"} size={28} color={v <= rating ? "#F59E0B" : "#D1D5DB"} />
              </Pressable>
            ))}
          </View>
          <TextInput
            style={{ borderWidth: 1, borderColor: "#E5E7EB", borderRadius: 10, padding: 10, fontSize: 13, color: DARK, backgroundColor: "#fff", minHeight: 60, textAlignVertical: "top" }}
            value={comment}
            onChangeText={setComment}
            placeholder="Update your review..."
            placeholderTextColor="#999"
            multiline
            maxLength={1000}
          />
          <View style={{ flexDirection: "row", justifyContent: "flex-end", gap: 8, marginTop: 10 }}>
            <Pressable onPress={() => setEditingReview(false)} style={{ paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8, backgroundColor: "#E5E7EB" }}>
              <Text fontSize={12} fontWeight="600" color={DARK}>Cancel</Text>
            </Pressable>
            <Pressable onPress={handleUpdate} disabled={updateMutation.isPending} style={{ paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8, backgroundColor: ACCENT }}>
              <Text fontSize={12} fontWeight="700" color="#fff">{updateMutation.isPending ? "Saving..." : "Update"}</Text>
            </Pressable>
          </View>
        </View>
      )}

      {/* Write new review */}
      {canReview && !hasReviewed && (
        showWriteReview ? (
          <View style={{ backgroundColor: "#FFF0F5", borderRadius: 12, padding: 14, marginBottom: 12 }}>
            <Text fontSize={13} fontWeight="700" color={DARK} mb="$2">Write a Review</Text>
            <View style={{ flexDirection: "row", gap: 6, marginBottom: 10 }}>
              {[1, 2, 3, 4, 5].map((v) => (
                <Pressable key={v} onPress={() => setRating(v)}>
                  <Ionicons name={v <= rating ? "star" : "star-outline"} size={28} color={v <= rating ? "#F59E0B" : "#D1D5DB"} />
                </Pressable>
              ))}
            </View>
            <TextInput
              style={{ borderWidth: 1, borderColor: "#E5E7EB", borderRadius: 10, padding: 10, fontSize: 13, color: DARK, backgroundColor: "#fff", minHeight: 60, textAlignVertical: "top" }}
              value={comment}
              onChangeText={setComment}
              placeholder="Share your experience... (optional)"
              placeholderTextColor="#999"
              multiline
              maxLength={1000}
            />
            <View style={{ flexDirection: "row", justifyContent: "flex-end", gap: 8, marginTop: 10 }}>
              <Pressable onPress={() => { setShowWriteReview(false); setRating(0); setComment(""); }} style={{ paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8, backgroundColor: "#E5E7EB" }}>
                <Text fontSize={12} fontWeight="600" color={DARK}>Cancel</Text>
              </Pressable>
              <Pressable onPress={handleSubmit} disabled={submitMutation.isPending || rating === 0} style={[{ paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8, backgroundColor: ACCENT }, (rating === 0) && { opacity: 0.5 }]}>
                <Text fontSize={12} fontWeight="700" color="#fff">{submitMutation.isPending ? "Submitting..." : "Submit"}</Text>
              </Pressable>
            </View>
          </View>
        ) : (
          <Pressable
            onPress={() => setShowWriteReview(true)}
            style={{ flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6, backgroundColor: "#FFF0F5", borderRadius: 10, paddingVertical: 10, marginBottom: 12, borderWidth: 1, borderColor: "#FFD6E7" }}
          >
            <Ionicons name="create-outline" size={16} color={ACCENT} />
            <Text fontSize={13} fontWeight="700" color={ACCENT}>Write a Review</Text>
          </Pressable>
        )
      )}

      {/* Review list */}
      {reviews.length > 0 ? (
        reviews.slice(0, 5).map((review: any) => (
          <View key={review.id} style={{ paddingVertical: 12, borderTopWidth: 1, borderTopColor: "#F0F0F5" }}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
              <View style={{ width: 32, height: 32, borderRadius: 16, backgroundColor: ACCENT, justifyContent: "center", alignItems: "center" }}>
                <Text fontSize={12} fontWeight="800" color="#fff">{(review.user?.name ?? "A")[0].toUpperCase()}</Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text fontSize={12} fontWeight="700" color={DARK}>{review.user?.name ?? "Anonymous"}</Text>
                <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
                  <StarRating rating={review.rating} size={11} />
                  <Text fontSize={10} color={GREY}>{new Date(review.created_at).toLocaleDateString()}</Text>
                </View>
              </View>
            </View>
            {review.messages && (
              <Text fontSize={12} color="#555" mt="$1" lineHeight={18}>{review.messages}</Text>
            )}
          </View>
        ))
      ) : (
        !canReview && (
          <View style={{ alignItems: "center", paddingVertical: 20 }}>
            <Ionicons name="chatbubble-outline" size={32} color="#D1D5DB" />
            <Text fontSize={12} color={GREY} mt="$2">No reviews yet</Text>
          </View>
        )
      )}
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: BG },
  loadC: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#fff" },
  errorIcon: {
    width: 80, height: 80, borderRadius: 40, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },
  goBackBtn: {
    flexDirection: "row", alignItems: "center", gap: 6, marginTop: 20,
    backgroundColor: ACCENT, borderRadius: 12, paddingHorizontal: 20, paddingVertical: 12,
  },

  // Hero
  hero: { position: "relative", backgroundColor: "#fff" },
  heroImg: { width: SW, height: SW, backgroundColor: "#fff" },
  heroGradient: {
    position: "absolute", top: 0, left: 0, right: 0, zIndex: 1,
  },
  overlayBtn: {
    position: "absolute", width: 38, height: 38, borderRadius: 19, zIndex: 2,
    backgroundColor: "rgba(255,255,255,0.95)", justifyContent: "center", alignItems: "center",
    elevation: 4, shadowColor: "#000", shadowOpacity: 0.12, shadowRadius: 6, shadowOffset: { width: 0, height: 2 },
  },
  iconOnlyBtn: {
    width: 38, height: 38, borderRadius: 19,
    justifyContent: "center", alignItems: "center",
  },
  imgCounter: {
    position: "absolute", bottom: 12, right: 16,
    backgroundColor: "rgba(0,0,0,0.5)", borderRadius: 12, paddingHorizontal: 10, paddingVertical: 4,
  },
  // Animated header
  fixedHeader: {
    position: "absolute", top: 0, left: 0, right: 0, zIndex: 100,
  },
  fixedHeaderInner: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingBottom: 12,
  },
  fixedHeaderBtn: {
    width: 38, height: 38, borderRadius: 19,
    justifyContent: "center", alignItems: "center",
  },
  shopActiveBtn: {
    backgroundColor: "#D1FAE5",
  },

  // Thumbnails
  thumbRow: { paddingHorizontal: 16, paddingVertical: 8, gap: 6, backgroundColor: "#fff" },
  thumbImg: {
    width: THUMB_SIZE, height: THUMB_SIZE, borderRadius: 8,
    borderWidth: 2, borderColor: "transparent", backgroundColor: "#F8F8F8",
  },
  thumbActive: { borderColor: ACCENT },

  // Cards
  card: {
    backgroundColor: "#fff", marginHorizontal: 12, marginTop: 10, borderRadius: 16,
    padding: 16, shadowColor: "#000", shadowOpacity: 0.04, shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 }, elevation: 1,
  },
  mainCard: {
    backgroundColor: "#fff", marginHorizontal: 0, borderRadius: 0,
    paddingHorizontal: 20, paddingTop: 20, paddingBottom: 16,
  },

  // Type badge
  typeBadge: { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 4 },

  // Flash Sale
  flashBanner: {
    backgroundColor: ACCENT, borderRadius: 12, padding: 12, marginBottom: 14,
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
  },
  flashLeft: { flexDirection: "row", alignItems: "center", gap: 10 },

  // Price
  priceSection: {},
  discountBadge: { backgroundColor: "#FF3D71", borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 },
  lockBadge: {
    flexDirection: "row", alignItems: "center", gap: 5,
    backgroundColor: "#FFF0F5", borderRadius: 8, paddingHorizontal: 12, paddingVertical: 6,
  },

  // Meta
  metaRow: { flexDirection: "row", flexWrap: "wrap", gap: 6, marginTop: 12 },
  metaChip: {
    flexDirection: "row", alignItems: "center", gap: 5,
    backgroundColor: BG, borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5,
  },
  metaDot: { width: 6, height: 6, borderRadius: 3 },

  // Tier badges
  sizeTierCard: {
    marginHorizontal: 12,
    marginTop: 10,
    padding: 14,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#FAD6E6",
    backgroundColor: "#FFF7FB",
  },
  sizeTierHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 10,
    marginBottom: 10,
  },
  sizeTierQtyBadge: {
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
    backgroundColor: "#FFEAF3",
  },
  sizeTierList: {
    gap: 8,
    paddingRight: 12,
  },
  sizeTierBadge: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: "#F5B8D0",
    backgroundColor: "#fff",
  },
  sizeTierBadgeActive: {
    borderColor: ACCENT,
    backgroundColor: ACCENT,
  },
  sizeTierMessage: {
    marginTop: 10,
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    backgroundColor: "#ECFDF5",
  },
  tierBadge: {
    alignItems: "center", paddingHorizontal: 16, paddingVertical: 10,
    borderRadius: 12, borderWidth: 1.5, borderColor: "#E5E5EA", backgroundColor: "#fff",
    minWidth: 100,
  },

  // Vendor
  vendorRow: { flexDirection: "row", alignItems: "center", gap: 10 },
  vendorLogo: {
    width: 38, height: 38, borderRadius: 19,
    backgroundColor: ACCENT, justifyContent: "center", alignItems: "center",
  },
  vendorArrow: {
    width: 28, height: 28, borderRadius: 14, backgroundColor: BG,
    justifyContent: "center", alignItems: "center",
  },

  // Variant tabs
  variantTab: {
    alignItems: "center", gap: 4, padding: 6, borderRadius: 10,
    borderWidth: 1.5, borderColor: "#E5E5EA", minWidth: 60, position: "relative",
  },
  variantTabImg: { width: 40, height: 40, borderRadius: 8, backgroundColor: BG },
  variantSwatch: { width: 40, height: 40, borderRadius: 20, borderWidth: 2, borderColor: "#E0E0E5" },
  variantFallback: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: BG,
    justifyContent: "center", alignItems: "center", borderWidth: 1, borderColor: "#E0E0E5",
  },
  variantQtyBadge: {
    position: "absolute", top: -6, right: -6, zIndex: 1,
    minWidth: 18, height: 18, borderRadius: 9, backgroundColor: ACCENT,
    justifyContent: "center", alignItems: "center", paddingHorizontal: 3,
  },
  variantPickerCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#E5E5EA",
    overflow: "hidden",
  },
  variantPickerHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 14,
    paddingTop: 14,
    paddingBottom: 10,
  },
  variantTotalBadge: {
    backgroundColor: "#FFF0F5",
    borderRadius: 10,
    paddingHorizontal: 9,
    paddingVertical: 4,
  },
  variantSummaryRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#F0F0F5",
  },
  variantSummaryLabel: { width: 54 },
  variantThumbList: { gap: 8, paddingRight: 8 },
  variantThumbButton: {
    width: 58,
    height: 58,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: "transparent",
    alignItems: "center",
    justifyContent: "center",
    position: "relative",
    backgroundColor: BG,
  },
  variantThumbButtonActive: {
    borderColor: ACCENT,
    backgroundColor: "#FFF0F5",
  },
  variantThumbImage: { width: 50, height: 50, borderRadius: 8, backgroundColor: BG },
  variantThumbSwatch: { width: 50, height: 50, borderRadius: 8, borderWidth: 1, borderColor: "#E5E5EA" },
  variantThumbFallback: {
    width: 50,
    height: 50,
    borderRadius: 8,
    backgroundColor: "#F8F8FA",
    alignItems: "center",
    justifyContent: "center",
  },
  variantOpenButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  variantSelectionSummary: {
    flexDirection: "row",
    flexWrap: "wrap",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 14,
    paddingBottom: 14,
  },
  selectedSizeChip: {
    maxWidth: "100%",
    backgroundColor: "#F5F5FA",
    borderRadius: 9,
    paddingHorizontal: 9,
    paddingVertical: 5,
  },

  // Size table
  sizeTable: { borderWidth: 1, borderColor: "#E5E5EA", borderRadius: 12, overflow: "hidden" },
  sizeTableHeader: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 8,
    backgroundColor: "#F9F9FB",
  },
  sizeTableRow: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 10,
    borderTopWidth: 1, borderTopColor: "#F0F0F5",
  },
  sizeTableTotal: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 10,
    borderTopWidth: 2, borderTopColor: "#E5E5EA", backgroundColor: "#F9F9FB",
  },
  sizeCol: {},
  miniStepBtn: {
    width: 28, height: 28, borderRadius: 8, borderWidth: 1.5, borderColor: "#E5E5EA",
    justifyContent: "center", alignItems: "center", backgroundColor: "#fff",
  },
  miniStepBtnDisabled: { borderColor: "#F0F0F5", backgroundColor: "#FAFAFA" },
  miniStepVal: { minWidth: 28, alignItems: "center", justifyContent: "center" },

  // Ordering section (no card wrapper)
  orderingSection: {
    marginHorizontal: 12, marginTop: 10, paddingVertical: 16,
  },

  // Simple product (no variants, single default size)
  simpleRow: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
  },
  simpleStepper: {
    flexDirection: "row", alignItems: "center", gap: 4,
  },
  simpleStepBtn: {
    width: 40, height: 40, borderRadius: 12, borderWidth: 1.5, borderColor: "#E5E5EA",
    justifyContent: "center", alignItems: "center", backgroundColor: "#fff",
  },
  simpleStepBtnDisabled: { borderColor: "#F0F0F5", backgroundColor: "#FAFAFA" },
  simpleStepVal: {
    minWidth: 50, alignItems: "center", justifyContent: "center",
    paddingHorizontal: 8,
  },

  // Selling price
  sellingPriceRow: {
    paddingHorizontal: 12, paddingVertical: 10,
    backgroundColor: "#FAFAFE",
    borderTopWidth: 1, borderTopColor: "#F0F0F5",
  },
  sellingPriceLabel: {
    flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 6,
  },
  sellingPriceInputRow: {
    flexDirection: "row", alignItems: "center", gap: 8,
  },
  sellingPriceInput: {
    flex: 1, height: 42, borderRadius: 10, borderWidth: 1.5, borderColor: "#E5E5EA",
    fontSize: 16, fontWeight: "700", backgroundColor: "#fff",
    paddingHorizontal: 12, color: DARK,
  },
  earningsBadge: {
    flexDirection: "row", alignItems: "center", gap: 4,
    paddingHorizontal: 10, paddingVertical: 6,
    backgroundColor: "#ECFDF5", borderRadius: 8,
  },

  // YouTube
  youtubeCard: {
    flexDirection: "row", alignItems: "center", gap: 12,
    backgroundColor: "#fff", marginHorizontal: 12, marginTop: 10,
    borderRadius: 16, padding: 14,
    elevation: 1, shadowColor: "#000", shadowOpacity: 0.04, shadowRadius: 8, shadowOffset: { width: 0, height: 2 },
  },
  ytIcon: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: "#FF0000",
    justifyContent: "center", alignItems: "center",
  },

  // Tabs
  tabBar: { flexDirection: "row", gap: 4 },
  tab: { paddingVertical: 8, paddingHorizontal: 14, borderRadius: 8 },
  tabActive: { backgroundColor: "#FFF0F5" },

  // Specs
  specRow: {
    flexDirection: "row", paddingVertical: 10,
    borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
  },
  specLabel: { width: "35%", fontSize: 13, color: GREY, fontWeight: "500" as any },
  specValue: { flex: 1, fontSize: 13, color: DARK, fontWeight: "600" as any },

  // Read more
  readMoreBtn: {
    flexDirection: "row", alignItems: "center", gap: 4, marginTop: 8, alignSelf: "flex-start",
  },
  descNativeWrap: {
    width: "100%",
  },
  descParagraph: {
    fontSize: 14,
    lineHeight: 23,
    color: "#444",
    marginBottom: 10,
  },
  descHeading: {
    fontSize: 16,
    lineHeight: 22,
    color: DARK,
    fontWeight: "700" as any,
    marginTop: 4,
    marginBottom: 8,
  },
  descHeadingLarge: {
    fontSize: 18,
    lineHeight: 25,
  },
  descListRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 8,
    marginBottom: 8,
  },
  descBullet: {
    width: 10,
    fontSize: 16,
    lineHeight: 22,
    color: ACCENT,
    fontWeight: "700" as any,
  },
  descListText: {
    flex: 1,
    fontSize: 14,
    lineHeight: 22,
    color: "#444",
  },
  descImage: {
    width: "100%",
    borderRadius: 12,
    backgroundColor: BG,
    marginVertical: 10,
  },

  // Delivery
  deliveryRow: {
    flexDirection: "row", alignItems: "center", gap: 12,
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
  },
  deliveryIcon: {
    width: 36, height: 36, borderRadius: 10, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },

  // Bottom bar
  bottomBar: {
    position: "absolute", bottom: 0, left: 0, right: 0,
    flexDirection: "row", alignItems: "center",
    paddingTop: 12, paddingHorizontal: 16, gap: 10,
    backgroundColor: "#fff", borderTopWidth: 1, borderTopColor: "#F0F0F0",
    elevation: 10, shadowColor: "#000", shadowOpacity: 0.1, shadowRadius: 10, shadowOffset: { width: 0, height: -4 },
  },
  cartBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: DARK,
    flexDirection: "row", justifyContent: "center", alignItems: "center", gap: 6,
  },
  buyBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: ACCENT,
    justifyContent: "center", alignItems: "center",
  },
  lockedBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: ACCENT,
    flexDirection: "row", justifyContent: "center", alignItems: "center", gap: 8,
  },

  // Activation Bottom Sheet
  sheetOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.4)", justifyContent: "flex-end" },
  sheetContainer: {
    backgroundColor: "#fff", borderTopLeftRadius: 24, borderTopRightRadius: 24,
    padding: 24, paddingBottom: 36, alignItems: "center",
  },
  sheetHandle: { width: 40, height: 4, borderRadius: 2, backgroundColor: "#E0E0E0", marginBottom: 16 },
  sheetIconWrap: {
    width: 72, height: 72, borderRadius: 36, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },
  sheetBenefits: {
    width: "100%", marginTop: 20, gap: 12,
    backgroundColor: "#FAFAFA", borderRadius: 14, padding: 16,
  },
  sheetBenefitRow: { flexDirection: "row", alignItems: "center" },
  sheetCta: {
    flexDirection: "row", alignItems: "center", justifyContent: "center",
    backgroundColor: ACCENT, borderRadius: 30, paddingVertical: 16,
    width: "100%", marginTop: 20, gap: 8,
  },

  // Variant selection bottom sheet
  variantSheetOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "flex-end",
  },
  variantSheetContainer: {
    backgroundColor: "#fff",
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "90%",
    minHeight: 400,
    overflow: "hidden",
    flexShrink: 1,
    flexGrow: 0,
  },
  variantSheetHandleWrap: {
    alignItems: "center",
    paddingTop: 12,
    paddingBottom: 8,
  },
  variantSheetHandle: {
    width: 56,
    height: 5,
    borderRadius: 3,
    backgroundColor: "#E5E5EA",
  },
  variantSheetHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 20,
    paddingTop: 6,
    paddingBottom: 14,
    borderBottomWidth: 1,
    borderBottomColor: "#EEEEF2",
  },
  variantSheetCloseBtn: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: "#F0F0F5",
    alignItems: "center",
    justifyContent: "center",
  },
  sheetTierPanel: {
    paddingHorizontal: 20,
    paddingVertical: 12,
    backgroundColor: "#FFF8E8",
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
  },
  sheetTierTitleRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 10,
  },
  sheetTierQtyBadge: {
    marginLeft: "auto",
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 3,
    backgroundColor: "#FFEAF3",
  },
  sheetTierBadge: {
    minWidth: 104,
    alignItems: "center",
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 9,
    borderWidth: 1,
    borderColor: "#E5E5EA",
    backgroundColor: "#fff",
  },
  sheetTierBadgeActive: {
    borderColor: ACCENT,
    backgroundColor: "#FFF0F5",
  },
  sheetTierMessage: {
    marginTop: 10,
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  sheetVariantPanel: {
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 10,
  },
  sheetVariantThumbList: {
    gap: 10,
    paddingTop: 10,
    paddingRight: 20,
  },
  sheetVariantThumb: {
    width: 58,
    height: 58,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: "transparent",
    alignItems: "center",
    justifyContent: "center",
    position: "relative",
    backgroundColor: "#fff",
  },
  sheetVariantThumbActive: {
    borderColor: ACCENT,
    backgroundColor: "#FFF0F5",
  },
  sheetVariantThumbImage: { width: 50, height: 50, borderRadius: 10, backgroundColor: BG },
  sheetVariantSwatch: { width: 50, height: 50, borderRadius: 10, borderWidth: 1, borderColor: "#E5E5EA" },
  sheetVariantFallback: {
    width: 50,
    height: 50,
    borderRadius: 10,
    backgroundColor: BG,
    alignItems: "center",
    justifyContent: "center",
  },
  sheetSizeHeader: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: "#EEEEF2",
    backgroundColor: "#FAFAFE",
  },
  sheetSizeRowWrap: {
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: "#EEEEF2",
    backgroundColor: "#fff",
    position: "relative" as const,
    overflow: "hidden" as const,
  },
  sheetSizeRowWrapActive: {
    backgroundColor: "#FFF8FB",
    borderBottomColor: "#F8D9E8",
  },
  sheetSizeRowAccent: {
    position: "absolute" as const,
    left: 0,
    top: 0,
    bottom: 0,
    width: 3,
    backgroundColor: ACCENT,
    borderTopRightRadius: 2,
    borderBottomRightRadius: 2,
  },
  sheetSizeRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    paddingHorizontal: 20,
    paddingVertical: 14,
  },
  sheetStepperWrap: {
    flex: 1.2,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "flex-end",
    gap: 4,
  },
  sheetStepBtn: {
    width: 32,
    height: 32,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: "#D8E1EF",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#fff",
  },
  sheetStepVal: {
    minWidth: 36,
    alignItems: "center",
    justifyContent: "center",
  },
  sheetSellingPricePanel: {
    paddingHorizontal: 20,
    paddingBottom: 12,
  },
  variantSheetFooter: {
    borderTopWidth: 1,
    borderTopColor: "#EEEEF2",
    backgroundColor: "#fff",
    paddingHorizontal: 20,
    paddingTop: 12,
    flexShrink: 0,
  },
  sheetSummaryCard: {
    backgroundColor: "#F8F8FC",
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#EEEEF2",
  },
  sheetTotalRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  sheetSellingPriceSection: {
    marginBottom: 14,
    paddingTop: 2,
  },
  sheetCurrencyBadge: {
    width: 36,
    height: 44,
    borderRadius: 10,
    backgroundColor: "#FFF0F5",
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: "#FECDD3",
  },
  sheetSellingPriceInput: {
    flex: 1,
    height: 44,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: "#E5E5EA",
    fontSize: 17,
    fontWeight: "700" as any,
    backgroundColor: "#FAFAFA",
    paddingHorizontal: 14,
    color: DARK,
  },
  sheetEarningsBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#ECFDF5",
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: "#BBF7D0",
  },
  sheetEarningsBadgeError: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#FEF2F2",
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  sheetActionRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginTop: 2,
  },
  sheetBuyBtn: {
    flex: 1,
    height: 52,
    borderRadius: 14,
    backgroundColor: ACCENT,
    alignItems: "center",
    justifyContent: "center",
    elevation: 3,
    shadowColor: ACCENT,
    shadowOpacity: 0.25,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 3 },
  },
  sheetCartBtn: {
    flex: 1,
    height: 52,
    borderRadius: 14,
    backgroundColor: DARK,
    alignItems: "center",
    justifyContent: "center",
    elevation: 3,
    shadowColor: DARK,
    shadowOpacity: 0.2,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 3 },
  },
  downloadBadge: {
    position: "absolute",
    bottom: 0,
    right: 0,
    backgroundColor: "#CCFF8D",
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderTopLeftRadius: 10,
  },
  downloadBadgeText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#1A1A2E",
  },
  descActions: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 8,
    gap: 12,
  },
  copyDescBtn: {
    flexDirection: "row",
    alignItems: "center",
    marginLeft: "auto",
    gap: 5,
    backgroundColor: "#FFF0F5",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#FECDD3",
  },
});
