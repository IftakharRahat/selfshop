import { useState, useEffect, useRef, useCallback, useMemo } from "react";
import {
  View, TextInput, FlatList, Pressable, StyleSheet, Dimensions,
  ActivityIndicator, Keyboard, StatusBar, ScrollView,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import * as SecureStore from "expo-secure-store";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";

const { width: SW } = Dimensions.get("window");
const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";
const CARD_GAP = 12;
const CARD_WIDTH = (SW - 32 - CARD_GAP) / 2;

const RECENT_KEY = "recent_searches";
const MAX_RECENT = 10;

type SortKey = "relevant" | "price_asc" | "price_desc" | "newest" | "discount";
const SORT_OPTIONS: { key: SortKey; label: string; icon: string }[] = [
  { key: "relevant", label: "Relevant", icon: "sparkles-outline" },
  { key: "price_asc", label: "Price: Low", icon: "arrow-up-outline" },
  { key: "price_desc", label: "Price: High", icon: "arrow-down-outline" },
  { key: "discount", label: "Discount", icon: "pricetag-outline" },
  { key: "newest", label: "Newest", icon: "time-outline" },
];

/* ── Image URL helper ── */
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string {
  if (!path || path.trim().length < 2) return "";
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

/* ═══════════════════════════════════════════════════ */
export default function SearchScreen() {
  const insets = useSafeAreaInsets();
  const inputRef = useRef<TextInput>(null);
  const [query, setQuery] = useState("");
  const [debouncedQuery, setDebouncedQuery] = useState("");
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [sortBy, setSortBy] = useState<SortKey>("relevant");

  // Auto-focus
  useEffect(() => {
    const t = setTimeout(() => inputRef.current?.focus(), 100);
    return () => clearTimeout(t);
  }, []);

  // Load recent searches
  useEffect(() => {
    SecureStore.getItemAsync(RECENT_KEY).then((v) => {
      if (v) {
        try { setRecentSearches(JSON.parse(v)); } catch {}
      }
    });
  }, []);

  // Debounce
  useEffect(() => {
    const t = setTimeout(() => setDebouncedQuery(query.trim()), 400);
    return () => clearTimeout(t);
  }, [query]);

  // Search API
  const { data, isLoading, isFetching } = useQuery({
    queryKey: ["search", debouncedQuery],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/search?keywords=${encodeURIComponent(debouncedQuery)}`);
      // API returns paginated: { data: { data: [...products] } } or flat: { data: [...products] }
      const items = d?.data?.data ?? d?.data ?? [];
      return Array.isArray(items) ? items : [];
    },
    enabled: debouncedQuery.length >= 2,
    staleTime: 60_000,
  });

  const rawProducts: any[] = data ?? [];

  // Client-side sort
  const products = useMemo(() => {
    if (sortBy === "relevant" || rawProducts.length === 0) return rawProducts;
    const sorted = [...rawProducts];
    switch (sortBy) {
      case "price_asc":
        sorted.sort((a, b) => Number(a.ProductSalePrice ?? a.ProductRegularPrice ?? 0) - Number(b.ProductSalePrice ?? b.ProductRegularPrice ?? 0));
        break;
      case "price_desc":
        sorted.sort((a, b) => Number(b.ProductSalePrice ?? b.ProductRegularPrice ?? 0) - Number(a.ProductSalePrice ?? a.ProductRegularPrice ?? 0));
        break;
      case "discount":
        sorted.sort((a, b) => Number(b.Discount ?? 0) - Number(a.Discount ?? 0));
        break;
      case "newest":
        sorted.sort((a, b) => Number(b.id ?? 0) - Number(a.id ?? 0));
        break;
    }
    return sorted;
  }, [rawProducts, sortBy]);

  // Save to recents
  const saveRecent = useCallback((term: string) => {
    setRecentSearches((prev) => {
      const filtered = prev.filter((s) => s.toLowerCase() !== term.toLowerCase());
      const updated = [term, ...filtered].slice(0, MAX_RECENT);
      SecureStore.setItemAsync(RECENT_KEY, JSON.stringify(updated));
      return updated;
    });
  }, []);

  // When results come in, save search
  useEffect(() => {
    if (debouncedQuery.length >= 2 && rawProducts.length > 0) {
      saveRecent(debouncedQuery);
    }
  }, [debouncedQuery, rawProducts.length]);

  const clearRecents = useCallback(() => {
    setRecentSearches([]);
    SecureStore.deleteItemAsync(RECENT_KEY);
  }, []);

  const selectRecent = useCallback((term: string) => {
    setQuery(term);
    setDebouncedQuery(term);
    Keyboard.dismiss();
  }, []);

  const clearQuery = useCallback(() => {
    setQuery("");
    setDebouncedQuery("");
    setSortBy("relevant");
    inputRef.current?.focus();
  }, []);

  const showResults = debouncedQuery.length >= 2;
  const showRecents = !showResults && recentSearches.length > 0;
  const searching = isLoading || isFetching;
  const hasResults = products.length > 0;

  return (
    <View style={[s.container, { paddingTop: insets.top }]}>
      <StatusBar translucent backgroundColor="transparent" barStyle="dark-content" />

      {/* ═══ SEARCH HEADER ═══ */}
      <View style={s.header}>
        <Pressable onPress={() => router.back()} hitSlop={8} style={s.backBtn}>
          <Ionicons name="arrow-back" size={22} color={DARK} />
        </Pressable>
        <View style={s.inputContainer}>
          <Ionicons name="search" size={18} color={GREY} />
          <TextInput
            ref={inputRef}
            style={s.input}
            placeholder="Search products..."
            placeholderTextColor="#B0B0B8"
            value={query}
            onChangeText={setQuery}
            returnKeyType="search"
            onSubmitEditing={() => {
              if (query.trim().length >= 2) {
                Keyboard.dismiss();
                saveRecent(query.trim());
              }
            }}
            autoCapitalize="none"
            autoCorrect={false}
          />
          {query.length > 0 && (
            <Pressable onPress={clearQuery} hitSlop={8}>
              <View style={s.clearBtn}>
                <Ionicons name="close" size={14} color="#fff" />
              </View>
            </Pressable>
          )}
        </View>
      </View>

      {/* ═══ SORT FILTER CHIPS (only when we have results) ═══ */}
      {showResults && hasResults && (
        <View style={s.filterBar}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={s.filterScroll}>
            {SORT_OPTIONS.map((opt) => {
              const active = sortBy === opt.key;
              return (
                <Pressable
                  key={opt.key}
                  onPress={() => setSortBy(opt.key)}
                  style={[s.filterChip, active && s.filterChipActive]}
                >
                  <Ionicons
                    name={opt.icon as any}
                    size={14}
                    color={active ? "#fff" : GREY}
                  />
                  <Text
                    fontSize={12}
                    fontWeight={active ? "700" : "500"}
                    color={active ? "#fff" : DARK}
                  >
                    {opt.label}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
        </View>
      )}

      {/* ═══ RECENT SEARCHES ═══ */}
      {showRecents && (
        <View style={s.recentsSection}>
          <View style={s.recentsHeader}>
            <Text fontSize={14} fontWeight="700" color={DARK}>Recent Searches</Text>
            <Pressable onPress={clearRecents} hitSlop={8}>
              <Text fontSize={13} fontWeight="600" color={ACCENT}>Clear All</Text>
            </Pressable>
          </View>
          {recentSearches.map((term, idx) => (
            <Pressable
              key={`${term}-${idx}`}
              style={s.recentItem}
              onPress={() => selectRecent(term)}
            >
              <Ionicons name="time-outline" size={18} color={GREY} />
              <Text fontSize={14} color={DARK} style={{ flex: 1 }}>{term}</Text>
              <Ionicons name="arrow-forward-outline" size={16} color="#CCC" />
            </Pressable>
          ))}
        </View>
      )}

      {/* ═══ IDLE STATE (no query, no recents) ═══ */}
      {!showResults && !showRecents && (
        <View style={s.topState}>
          <View style={s.emptyIcon}>
            <Ionicons name="search" size={40} color="#D1D1D6" />
          </View>
          <Text fontSize={15} fontWeight="600" color={DARK} mt="$2">Search Products</Text>
          <Text fontSize={13} color={GREY} mt="$1">Find products by name, category, or keyword</Text>
        </View>
      )}

      {/* ═══ LOADING ═══ */}
      {showResults && searching && rawProducts.length === 0 && (
        <View style={s.skeletonGrid}>
          {Array.from({ length: 6 }).map((_, i) => (
            <View key={i} style={s.skeletonCard}>
              <View style={s.skeletonImage} />
              <View style={[s.skeletonLine, { width: "80%", marginTop: 10 }]} />
              <View style={[s.skeletonLine, { width: "50%", marginTop: 6 }]} />
            </View>
          ))}
        </View>
      )}

      {/* ═══ RESULTS ═══ */}
      {showResults && hasResults && (
        <FlatList
          data={products}
          numColumns={2}
          columnWrapperStyle={s.gridRow}
          contentContainerStyle={s.gridContent}
          keyExtractor={(item, i) => item.ProductSlug || String(i)}
          showsVerticalScrollIndicator={false}
          keyboardDismissMode="on-drag"
          ListHeaderComponent={
            <Text fontSize={13} color={GREY} mb="$2" style={{ paddingHorizontal: 16 }}>
              {products.length} result{products.length !== 1 ? "s" : ""} for "{debouncedQuery}"
            </Text>
          }
          renderItem={({ item }) => (
            <View style={{ width: CARD_WIDTH }}>
              <ProductCard
                name={item.ProductName ?? ""}
                price={`৳${Number(item.ProductSellingPrice ?? item.ProductSalePrice ?? item.ProductRegularPrice ?? 0).toLocaleString("en-BD")}`}
                image={resolveImageUrl(item.ViewProductImage)}
                slug={item.ProductSlug}
                category={item.category_name ?? ""}
              />
            </View>
          )}
        />
      )}

      {/* ═══ INLINE LOADING INDICATOR ═══ */}
      {showResults && searching && hasResults && (
        <View style={s.inlineLoader}>
          <ActivityIndicator size="small" color={ACCENT} />
          <Text fontSize={12} color={GREY} ml="$2">Updating results...</Text>
        </View>
      )}

      {/* ═══ NO RESULTS ═══ */}
      {showResults && !searching && !hasResults && (
        <View style={s.topState}>
          <View style={[s.emptyIcon, { backgroundColor: "#FFF0F5" }]}>
            <Ionicons name="search-outline" size={40} color={ACCENT} />
          </View>
          <Text fontSize={16} fontWeight="700" color={DARK} mt="$3">No Results Found</Text>
          <Text fontSize={13} color={GREY} mt="$1" style={{ maxWidth: 260, textAlign: "center" }}>
            We couldn't find anything for "{debouncedQuery}". Try different keywords.
          </Text>
        </View>
      )}
    </View>
  );
}

/* ═══ STYLES ═══ */
const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },

  // Header
  header: {
    flexDirection: "row", alignItems: "center", gap: 10,
    paddingHorizontal: 16, paddingVertical: 12,
    borderBottomWidth: 1, borderBottomColor: "#F0F0F5",
  },
  backBtn: {
    width: 38, height: 38, borderRadius: 19,
    justifyContent: "center", alignItems: "center",
  },
  inputContainer: {
    flex: 1, flexDirection: "row", alignItems: "center", gap: 10,
    backgroundColor: BG, borderRadius: 14,
    paddingHorizontal: 14, height: 46,
  },
  input: {
    flex: 1, fontSize: 15, color: DARK, fontFamily: "Inter", padding: 0,
  },
  clearBtn: {
    width: 22, height: 22, borderRadius: 11, backgroundColor: "#C7C7CC",
    justifyContent: "center", alignItems: "center",
  },

  // Filter bar
  filterBar: {
    borderBottomWidth: 1, borderBottomColor: "#F0F0F5",
  },
  filterScroll: {
    paddingHorizontal: 16, paddingVertical: 10, gap: 8,
  },
  filterChip: {
    flexDirection: "row", alignItems: "center", gap: 5,
    paddingHorizontal: 14, paddingVertical: 8,
    borderRadius: 20, backgroundColor: BG,
    borderWidth: 1, borderColor: "#E5E5EA",
  },
  filterChipActive: {
    backgroundColor: ACCENT, borderColor: ACCENT,
  },

  // Recents
  recentsSection: { paddingTop: 8 },
  recentsHeader: {
    flexDirection: "row", justifyContent: "space-between", alignItems: "center",
    paddingHorizontal: 20, paddingVertical: 10,
  },
  recentItem: {
    flexDirection: "row", alignItems: "center", gap: 12,
    paddingHorizontal: 20, paddingVertical: 13,
    borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: "#F0F0F5",
  },

  // Top-aligned state (visible above keyboard)
  topState: {
    alignItems: "center", paddingTop: 60, paddingHorizontal: 32,
  },
  emptyIcon: {
    width: 80, height: 80, borderRadius: 40,
    backgroundColor: BG, justifyContent: "center", alignItems: "center",
  },

  // Skeleton
  skeletonGrid: {
    flexDirection: "row", flexWrap: "wrap", gap: CARD_GAP,
    paddingHorizontal: 16, paddingTop: 20,
  },
  skeletonCard: {
    width: CARD_WIDTH, borderRadius: 14, backgroundColor: BG,
    padding: 10, marginBottom: 4,
  },
  skeletonImage: {
    width: "100%", aspectRatio: 1, borderRadius: 10,
    backgroundColor: "#E8E8ED",
  },
  skeletonLine: {
    height: 12, borderRadius: 6, backgroundColor: "#E8E8ED",
  },

  // Grid
  gridRow: { gap: CARD_GAP, paddingHorizontal: 16 },
  gridContent: { paddingTop: 12, paddingBottom: 100 },

  // Inline loader
  inlineLoader: {
    position: "absolute", bottom: 20, alignSelf: "center",
    flexDirection: "row", alignItems: "center",
    backgroundColor: "#fff", borderRadius: 20,
    paddingHorizontal: 16, paddingVertical: 8,
    elevation: 4, shadowColor: "#000", shadowOpacity: 0.1,
    shadowRadius: 8, shadowOffset: { width: 0, height: 2 },
  },
});
