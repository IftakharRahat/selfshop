import { useState } from "react";
import { router } from "expo-router";
import {
  View,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Image,
  Pressable,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { useQuery } from "@tanstack/react-query";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import apiClient from "@/lib/api-client";
import { CategoriesSkeleton } from "@/components/skeleton";

const SCREEN_WIDTH = Dimensions.get("window").width;
const SIDEBAR_WIDTH = 86;

export default function CategoriesScreen() {
  const insets = useSafeAreaInsets();
  const [selectedCatId, setSelectedCatId] = useState<number | null>(null);

  const categories = useQuery({
    queryKey: ["categories", "active"],
    queryFn: async () => {
      const { data } = await apiClient.get("/categories");
      return data?.data ?? data ?? [];
    },
  });
  const categoryList: any[] = categories.data ?? [];

  const activeCatId = selectedCatId ?? categoryList[0]?.id ?? null;
  const selectedCat = categoryList.find((c: any) => c.id === activeCatId);
  const subcategories: any[] = selectedCat?.subcategories ?? [];

  if (categories.isLoading) {
    return <CategoriesSkeleton />;
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={[styles.header, { paddingTop: insets.top + 12 }]}>
        <Text fontSize="$7" fontWeight="bold" color="#1A1A2E">
          Categories
        </Text>
      </View>

      <View style={styles.body}>
        {/* Left Sidebar */}
        <ScrollView
          style={styles.sidebar}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 120, paddingTop: 8 }}
        >
          {categoryList.map((cat: any) => {
            const isActive = cat.id === activeCatId;
            return (
              <Pressable
                key={cat.id}
                style={[styles.sidebarItem, isActive && styles.sidebarItemActive]}
                onPress={() => setSelectedCatId(cat.id)}
              >
                {isActive && <View style={styles.activeIndicator} />}
                <View style={[styles.sidebarIcon, isActive && styles.sidebarIconActive]}>
                  <Image
                    source={{ uri: cat.category_icon }}
                    style={styles.sidebarImage}
                    resizeMode="cover"
                  />
                </View>
                <Text
                  fontSize={10}
                  color={isActive ? "#E5005F" : "#666"}
                  fontWeight={isActive ? "700" : "400"}
                  textAlign="center"
                  numberOfLines={2}
                  style={{ lineHeight: 13 }}
                >
                  {cat.category_name}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>

        {/* Right Content */}
        <ScrollView
          style={styles.content}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ paddingBottom: 120 }}
        >
          {selectedCat && (
            <View style={styles.contentHeader}>
              <Text fontSize="$5" fontWeight="bold" color="#1A1A2E">
                {selectedCat.category_name}
              </Text>
            </View>
          )}

          {subcategories.length === 0 && (
            <View style={styles.emptyContent}>
              <Text fontSize="$3" color="#8E8E93">
                No subcategories
              </Text>
            </View>
          )}

          {subcategories.map((sub: any) => {
            const miniList: any[] = sub.minicategories ?? [];
            return (
              <View key={sub.id} style={styles.subSection}>
                {/* Subcategory header */}
                <Pressable
                  style={styles.subHeader}
                  onPress={() => router.push({
                    pathname: "/category-products",
                    params: { type: "subcategory", slug: sub.slug, title: sub.sub_category_name },
                  } as any)}
                >
                  <View style={styles.subIconWrapper}>
                    <Image
                      source={{ uri: sub.subcategory_icon }}
                      style={styles.subIcon}
                      resizeMode="cover"
                    />
                  </View>
                  <Text fontSize="$3" fontWeight="700" color="#1A1A2E" style={{ flex: 1 }}>
                    {sub.sub_category_name}
                  </Text>
                </Pressable>

                {/* Minicategories card grid */}
                {miniList.length > 0 && (
                  <View style={styles.miniGrid}>
                    {miniList.map((mini: any) => (
                      <Pressable
                        key={mini.id}
                        style={({ pressed }) => [
                          styles.miniItem,
                          pressed && { opacity: 0.7 },
                        ]}
                        onPress={() => router.push({
                          pathname: "/category-products",
                          params: { type: "minicategory", slug: mini.slug, title: mini.mini_category_name },
                        } as any)}
                      >
                        <View style={styles.miniIconWrapper}>
                          <Image
                            source={{ uri: mini.minicategory_icon }}
                            style={styles.miniIcon}
                            resizeMode="cover"
                          />
                        </View>
                        <Text fontSize={10} color="#444" textAlign="center" numberOfLines={2}>
                          {mini.mini_category_name}
                        </Text>
                      </Pressable>
                    ))}
                  </View>
                )}
              </View>
            );
          })}
        </ScrollView>
      </View>
    </View>
  );
}

const CONTENT_WIDTH = SCREEN_WIDTH - SIDEBAR_WIDTH;
const MINI_COLS = 3;
const MINI_GAP = 8;
const MINI_PADDING = 14;
const MINI_ITEM_WIDTH = Math.floor(
  (CONTENT_WIDTH - MINI_PADDING * 2 - MINI_GAP * (MINI_COLS - 1)) / MINI_COLS
);

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
  },
  header: {
    paddingHorizontal: 20,
    paddingBottom: 14,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
  },
  body: {
    flex: 1,
    flexDirection: "row",
  },

  // Sidebar
  sidebar: {
    width: SIDEBAR_WIDTH,
    maxWidth: SIDEBAR_WIDTH,
    flexGrow: 0,
    flexShrink: 0,
    backgroundColor: "#FAFAFA",
    borderRightWidth: 1,
    borderRightColor: "#F0F0F5",
  },
  sidebarItem: {
    alignItems: "center",
    paddingVertical: 10,
    paddingHorizontal: 4,
    gap: 4,
    position: "relative",
  },
  sidebarItemActive: {
    backgroundColor: "#fff",
  },
  activeIndicator: {
    position: "absolute",
    left: 0,
    top: 6,
    bottom: 6,
    width: 3,
    borderRadius: 2,
    backgroundColor: "#E5005F",
  },
  sidebarIcon: {
    width: 44,
    height: 44,
    borderRadius: 10,
    backgroundColor: "#fff",
    overflow: "hidden",
    borderWidth: 2,
    borderColor: "#E0E0E5",
  },
  sidebarIconActive: {
    borderColor: "#E5005F",
    backgroundColor: "#FFF5F8",
  },
  sidebarImage: {
    width: "100%",
    height: "100%",
  },

  // Content panel
  content: {
    flex: 1,
    backgroundColor: "#fff",
  },
  contentHeader: {
    paddingHorizontal: MINI_PADDING,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
  },
  emptyContent: {
    alignItems: "center",
    paddingTop: 40,
  },
  subSection: {
    paddingHorizontal: MINI_PADDING,
    paddingTop: 14,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F5F5FA",
  },
  subHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 10,
  },
  subIconWrapper: {
    width: 28,
    height: 28,
    borderRadius: 6,
    backgroundColor: "#F5F5FA",
    overflow: "hidden",
  },
  subIcon: {
    width: "100%",
    height: "100%",
  },
  miniGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: MINI_GAP,
  },
  miniItem: {
    width: MINI_ITEM_WIDTH,
    alignItems: "center",
    gap: 4,
    paddingVertical: 6,
  },
  miniIconWrapper: {
    width: 44,
    height: 44,
    borderRadius: 10,
    backgroundColor: "#F5F5FA",
    overflow: "hidden",
  },
  miniIcon: {
    width: "100%",
    height: "100%",
  },
});
