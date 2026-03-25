import { useState, useCallback, useRef } from "react";
import {
  View, ScrollView, Image, Pressable, StyleSheet, Dimensions,
  ActivityIndicator, FlatList, type ViewToken,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";
import { useSession } from "@/lib/auth-client";

const { width: SW } = Dimensions.get("window");
const IH = SW * 0.95;
const ACCENT = "#E5005F";
const BLUE = "#3257D9";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";

export default function ProductDetailScreen() {
  const params = useLocalSearchParams<{ slug: string }>();
  const insets = useSafeAreaInsets();
  const slug = Array.isArray(params.slug) ? params.slug[0] : params.slug;
  const [selVar, setSelVar] = useState(0);
  const [qty, setQty] = useState(1);
  const [imgIdx, setImgIdx] = useState(0);
  const [wishlisted, setWishlisted] = useState(false);
  const { data: session } = useSession();
  const loggedIn = !!session?.user;
  const imgRef = useRef<FlatList>(null);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["product", slug],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/products/${slug}`);
      return d?.data ?? d;
    },
    enabled: !!slug && slug.length > 0,
  });

  const onImgChange = useCallback(({ viewableItems }: { viewableItems: ViewToken[] }) => {
    if (viewableItems.length > 0 && viewableItems[0].index !== null) setImgIdx(viewableItems[0].index);
  }, []);
  const imgViewCfg = useRef({ viewAreaCoveragePercentThreshold: 50 }).current;

  if (isLoading) return <View style={s.loadC}><ActivityIndicator size="large" color={ACCENT} /></View>;
  if (isError || !data) return (
    <View style={s.loadC}>
      <Ionicons name="alert-circle-outline" size={48} color={GREY} />
      <Text fontSize="$4" color={GREY} mt="$3">Product not found</Text>
      <Pressable style={s.backBtn} onPress={() => router.back()}><Text fontSize="$3" color={ACCENT} fontWeight="600">Go back</Text></Pressable>
    </View>
  );

  const product = data.product ?? data;
  const variants = data.variants ?? product.variants ?? [];
  const reviewStats = data.reviewStats ?? data.review_stats ?? { totalReviews: 0, averageRating: 0 };
  const allImgs = [product.image, ...(product.images?.map((i: any) => i.imageUrl ?? i.image_url) ?? [])].filter(Boolean);
  const sv = variants[selVar];
  const price = sv ? sv.price : product.price;
  const oMin = sv ? Number(sv.orderMin ?? sv.order_min ?? 1) : 1;
  const oMax = sv?.orderMax ?? sv?.order_max ? Number(sv.orderMax ?? sv.order_max) : 999;
  const oInc = sv ? Number(sv.orderIncrement ?? sv.order_increment ?? 1) : 1;
  const features = (product.features as Array<{ title: string; items: Array<{ key: string; value: string }> }>) ?? [];

  return (
    <View style={s.container}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 100 }}>
        {/* Hero */}
        <View style={s.hero}>
          <FlatList ref={imgRef} data={allImgs} horizontal pagingEnabled showsHorizontalScrollIndicator={false} bounces={false} onViewableItemsChanged={onImgChange} viewabilityConfig={imgViewCfg} keyExtractor={(_, i) => String(i)} renderItem={({ item }) => <Image source={{ uri: item }} style={s.heroImg} resizeMode="cover" />} />
          <Pressable style={[s.oBtn, { top: insets.top + 8, left: 16 }]} onPress={() => router.back()}><Ionicons name="arrow-back" size={22} color={DARK} /></Pressable>
          <Pressable style={[s.oBtn, { top: insets.top + 8, right: 16 }]}><Ionicons name="share-outline" size={20} color={DARK} /></Pressable>
          {allImgs.length > 1 && <View style={s.dots}>{allImgs.map((_, i) => <View key={i} style={[s.dot, imgIdx === i ? s.dotA : s.dotI]} />)}</View>}
        </View>
        {/* Price */}
        <View style={s.pCard}>
          <View style={s.pRow}><Image source={{ uri: product.image }} style={s.thumb} /><View style={{ flex: 1 }}><Text fontSize="$8" fontWeight="bold" color={DARK}>৳{Number(price).toLocaleString()}</Text></View></View>
          {(reviewStats.totalReviews ?? 0) > 0 && <View style={s.revRow}><Ionicons name="star" size={16} color="#FFC107" /><Text fontSize="$3" fontWeight="600" color={DARK} ml="$1">{reviewStats.averageRating?.toFixed(1)}</Text><Text fontSize="$2" color={GREY} ml="$1">({reviewStats.totalReviews})</Text></View>}
        </View>
        {/* Variants */}
        {variants.length > 0 && <View style={s.sec}><Text fontSize="$5" fontWeight="bold" color={DARK} mb="$3">Variants</Text><ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 12 }}>{variants.map((v: any, i: number) => (<Pressable key={v.id} style={[s.vCard, i === selVar && s.vSel]} onPress={() => { setSelVar(i); setQty(Number(v.orderMin ?? v.order_min ?? 1)); }}>{i === selVar && <View style={s.vChk}><Ionicons name="checkmark-circle" size={20} color={BLUE} /></View>}<Text fontSize="$3" fontWeight="600" color={i === selVar ? BLUE : DARK}>{v.unitLabel ?? v.unit_label}</Text><Text fontSize="$2" color={i === selVar ? BLUE : GREY} mt="$0.5">৳{Number(v.price).toLocaleString()}</Text></Pressable>))}</ScrollView></View>}
        {/* Quantity */}
        <View style={s.sec}><View style={s.qRow}><Text fontSize="$5" fontWeight="bold" color={DARK}>Quantity</Text><View style={s.stepper}><Pressable style={[s.sBtn, qty <= oMin && s.sBtnD]} onPress={() => setQty(p => Math.max(oMin, p - oInc))} disabled={qty <= oMin}><Ionicons name="remove" size={22} color={qty <= oMin ? "#ccc" : BLUE} /></Pressable><View style={s.sVal}><Text fontSize="$5" fontWeight="bold" color={DARK}>{qty}</Text></View><Pressable style={[s.sBtn, qty >= oMax && s.sBtnD]} onPress={() => setQty(p => Math.min(oMax, p + oInc))} disabled={qty >= oMax}><Ionicons name="add" size={22} color={qty >= oMax ? "#ccc" : BLUE} /></Pressable></View></View></View>
        {/* Desc */}
        {product.description && <View style={s.sec}><Text fontSize="$5" fontWeight="bold" color={DARK} mb="$2">Description</Text><Text fontSize="$3" color="#444" lineHeight={22}>{product.description}</Text></View>}
        {/* Features */}
        {features.map((g, gi) => <View key={gi} style={s.sec}><Text fontSize="$4" fontWeight="bold" color={DARK} mb="$2">{g.title}</Text>{g.items.map((it, ii) => <View key={ii} style={s.fRow}><Text fontSize="$3" color={GREY} style={{ width: "40%" }}>{it.key}</Text><Text fontSize="$3" color={DARK} style={{ width: "60%" }}>{it.value}</Text></View>)}</View>)}
        {/* Meta */}
        <View style={s.sec}><View style={s.mRow}><Ionicons name="pricetag-outline" size={16} color={GREY} /><Text fontSize="$2" color={GREY} ml="$1.5">Category: {product.category?.name}</Text></View></View>
      </ScrollView>
      {/* Bottom bar */}
      <View style={[s.bBar, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        <Pressable style={({ pressed }) => [s.wBtn, pressed && { opacity: 0.7 }]} onPress={() => { if (!loggedIn) { toast.error("Sign in required"); return; } setWishlisted(p => { toast.success(!p ? "Added to wishlist" : "Removed"); return !p; }); }}>
          <Ionicons name={wishlisted ? "heart" : "heart-outline"} size={24} color={wishlisted ? "#EF4444" : DARK} />
        </Pressable>
        <Pressable style={({ pressed }) => [s.cartBtn, pressed && { opacity: 0.85 }]} onPress={() => { if (!loggedIn) { toast.error("Sign in required"); return; } toast.success("Added to cart"); }}>
          <Text fontSize="$3" fontWeight="bold" color="#fff">Add to cart</Text>
        </Pressable>
        <Pressable style={({ pressed }) => [s.buyBtn, pressed && { opacity: 0.85 }]} onPress={() => { if (!loggedIn) { toast.error("Sign in required"); return; } toast.success("Proceeding to checkout"); }}>
          <Text fontSize="$3" fontWeight="bold" color="#fff">Buy now</Text>
        </Pressable>
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  loadC: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#fff" },
  backBtn: { marginTop: 16, paddingVertical: 8, paddingHorizontal: 20 },
  hero: { position: "relative", backgroundColor: "#F8F8F8" },
  heroImg: { width: SW, height: IH },
  oBtn: { position: "absolute", width: 40, height: 40, borderRadius: 20, backgroundColor: "rgba(255,255,255,0.9)", justifyContent: "center", alignItems: "center", elevation: 3 },
  dots: { position: "absolute", bottom: 16, left: 0, right: 0, flexDirection: "row", justifyContent: "center", gap: 6 },
  dot: { height: 6, borderRadius: 3 },
  dotA: { width: 20, backgroundColor: ACCENT },
  dotI: { width: 6, backgroundColor: "rgba(255,255,255,0.7)" },
  pCard: { paddingHorizontal: 20, paddingVertical: 18, backgroundColor: "#FDF2F8", borderTopLeftRadius: 24, borderTopRightRadius: 24, marginTop: -20 },
  pRow: { flexDirection: "row", alignItems: "center", gap: 14 },
  thumb: { width: 56, height: 56, borderRadius: 12, backgroundColor: "#eee" },
  revRow: { flexDirection: "row", alignItems: "center", marginTop: 12 },
  sec: { paddingHorizontal: 20, paddingTop: 20 },
  vCard: { backgroundColor: "#F8F8F8", borderRadius: 14, padding: 14, minWidth: 100, alignItems: "center", borderWidth: 2, borderColor: "transparent", position: "relative" },
  vSel: { borderColor: BLUE, backgroundColor: "#EFF6FF" },
  vChk: { position: "absolute", top: -6, right: -6, backgroundColor: "#fff", borderRadius: 12 },
  qRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  stepper: { flexDirection: "row", alignItems: "center", gap: 4 },
  sBtn: { width: 44, height: 44, borderRadius: 22, borderWidth: 2, borderColor: BLUE, justifyContent: "center", alignItems: "center" },
  sBtnD: { borderColor: "#E5E5E5" },
  sVal: { minWidth: 48, alignItems: "center", justifyContent: "center", paddingHorizontal: 8 },
  fRow: { flexDirection: "row", paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: "#F5F5F5" },
  mRow: { flexDirection: "row", alignItems: "center" },
  bBar: { position: "absolute", bottom: 0, left: 0, right: 0, flexDirection: "row", alignItems: "center", paddingTop: 12, paddingHorizontal: 16, gap: 10, backgroundColor: "#fff", borderTopWidth: 1, borderTopColor: "#F0F0F0", elevation: 8 },
  wBtn: { width: 52, height: 52, borderRadius: 14, borderWidth: 1.5, borderColor: "#E5E5E5", justifyContent: "center", alignItems: "center" },
  cartBtn: { flex: 1, height: 52, borderRadius: 14, backgroundColor: DARK, justifyContent: "center", alignItems: "center" },
  buyBtn: { flex: 1.2, height: 52, borderRadius: 14, backgroundColor: BLUE, justifyContent: "center", alignItems: "center" },
});
