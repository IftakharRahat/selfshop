import React, { useState, useMemo } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Modal,
  TextInput,
  FlatList,
  SafeAreaView,
  Platform,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";

const BRAND_PRIMARY = "#4f46e5";

/* ── Types ── */
export interface MiniCategory {
  id: number;
  mini_category_name: string;
  subcategory_id: number;
}

export interface SubCategory {
  id: number;
  sub_category_name: string;
  category_id: number;
  minicategories?: MiniCategory[];
}

export interface Category {
  id: number;
  category_name: string;
  subcategories?: SubCategory[];
}

export interface CategorySelection {
  categoryId: number | null;
  categoryName: string;
  subcategoryId: number | null;
  subcategoryName: string;
  minicategoryId: number | null;
  minicategoryName: string;
}

interface CategoryPickerProps {
  categories: Category[];
  selection: CategorySelection;
  onChange: (sel: CategorySelection) => void;
  error?: string;
}

/* ── Main Picker Button + Modal ── */
export default function CategoryPicker({
  categories,
  selection,
  onChange,
  error,
}: CategoryPickerProps) {
  // Sanitize categories at the root: filter out null entries at every level
  const safeCategories = useMemo(() => {
    try {
      if (!Array.isArray(categories)) {
        console.warn("[CategoryPicker] categories is not an array:", typeof categories);
        return [];
      }
      const result = categories
        .filter((c: any) => {
          if (c == null || typeof c !== "object" || !c.category_name) {
            console.warn("[CategoryPicker] filtered out invalid category:", c);
            return false;
          }
          return true;
        })
        .map((c: any) => ({
          ...c,
          subcategories: Array.isArray(c.subcategories)
            ? c.subcategories
                .filter((s: any) => s != null && typeof s === "object" && s.sub_category_name)
                .map((s: any) => ({
                  ...s,
                  minicategories: Array.isArray(s.minicategories)
                    ? s.minicategories.filter((m: any) => m != null && typeof m === "object" && m.mini_category_name)
                    : [],
                }))
            : [],
        }));
      return result;
    } catch (e) {
      console.error("[CategoryPicker] safeCategories error:", e);
      return [];
    }
  }, [categories]);

  const [visible, setVisible] = useState(false);
  const [level, setLevel] = useState<"category" | "subcategory" | "minicategory">("category");
  const [search, setSearch] = useState("");
  const [tempCategory, setTempCategory] = useState<Category | null>(null);
  const [tempSubcategory, setTempSubcategory] = useState<SubCategory | null>(null);

  const hasSelection = selection.categoryId !== null;

  const openPicker = () => {
    setLevel("category");
    setSearch("");
    setTempCategory(null);
    setTempSubcategory(null);
    setVisible(true);
  };

  const selectCategory = (cat: Category) => {
    setTempCategory(cat);
    if (cat.subcategories && cat.subcategories.length > 0) {
      setLevel("subcategory");
      setSearch("");
    } else {
      // No subcategories — select directly
      onChange({
        categoryId: cat.id,
        categoryName: cat.category_name,
        subcategoryId: null,
        subcategoryName: "",
        minicategoryId: null,
        minicategoryName: "",
      });
      setVisible(false);
    }
  };

  const selectSubcategory = (sub: SubCategory) => {
    setTempSubcategory(sub);
    if (sub.minicategories && sub.minicategories.length > 0) {
      setLevel("minicategory");
      setSearch("");
    } else {
      // No mini-categories — select directly
      onChange({
        categoryId: tempCategory!.id,
        categoryName: tempCategory!.category_name,
        subcategoryId: sub.id,
        subcategoryName: sub.sub_category_name,
        minicategoryId: null,
        minicategoryName: "",
      });
      setVisible(false);
    }
  };

  const selectMiniCategory = (mini: MiniCategory) => {
    onChange({
      categoryId: tempCategory!.id,
      categoryName: tempCategory!.category_name,
      subcategoryId: tempSubcategory!.id,
      subcategoryName: tempSubcategory!.sub_category_name,
      minicategoryId: mini.id,
      minicategoryName: mini.mini_category_name,
    });
    setVisible(false);
  };

  // Filter data based on search — uses safeCategories (already sanitized)
  const filteredData = useMemo(() => {
    const q = search.toLowerCase().trim();
    if (level === "category") {
      return q
        ? safeCategories.filter((c) => c.category_name.toLowerCase().includes(q))
        : safeCategories;
    }
    if (level === "subcategory") {
      const subs = tempCategory?.subcategories ?? [];
      return q
        ? subs.filter((s) => s.sub_category_name.toLowerCase().includes(q))
        : subs;
    }
    if (level === "minicategory") {
      const minis = tempSubcategory?.minicategories ?? [];
      return q
        ? minis.filter((m) => m.mini_category_name.toLowerCase().includes(q))
        : minis;
    }
    return [];
  }, [level, search, safeCategories, tempCategory, tempSubcategory]);

  const getTitle = () => {
    if (level === "category") return "Select Category";
    if (level === "subcategory") return "Select Subcategory";
    return "Select Child Category";
  };

  const getItemLabel = (item: any) => {
    if (level === "category") return item?.category_name ?? "Unknown";
    if (level === "subcategory") return item?.sub_category_name ?? "Unknown";
    return item?.mini_category_name ?? "Unknown";
  };

  const handleBack = () => {
    if (level === "minicategory") {
      setLevel("subcategory");
      setSearch("");
    } else if (level === "subcategory") {
      setLevel("category");
      setSearch("");
    } else {
      setVisible(false);
    }
  };

  const handleItemPress = (item: any) => {
    if (level === "category") selectCategory(item);
    else if (level === "subcategory") selectSubcategory(item);
    else selectMiniCategory(item);
  };

  // Build breadcrumb for display
  const breadcrumb = [];
  if (selection.categoryName) breadcrumb.push(selection.categoryName);
  if (selection.subcategoryName) breadcrumb.push(selection.subcategoryName);
  if (selection.minicategoryName) breadcrumb.push(selection.minicategoryName);

  return (
    <View style={styles.outerContainer}>
      <Text style={styles.label}>
        Category <Text style={styles.required}>*</Text>
      </Text>

      <TouchableOpacity
        style={[styles.pickerButton, error ? styles.pickerButtonError : null]}
        onPress={openPicker}
        activeOpacity={0.7}
      >
        {hasSelection ? (
          <View style={styles.breadcrumbRow}>
            {breadcrumb.map((crumb, i) => (
              <View key={i} style={styles.breadcrumbChip}>
                {i > 0 && (
                  <Ionicons
                    name="chevron-forward"
                    size={10}
                    color="#9ca3af"
                    style={{ marginRight: 2 }}
                  />
                )}
                <Text style={styles.breadcrumbText}>{crumb}</Text>
              </View>
            ))}
          </View>
        ) : (
          <Text style={styles.pickerPlaceholder}>Tap to select category</Text>
        )}
        <Ionicons name="chevron-down" size={18} color="#9ca3af" />
      </TouchableOpacity>

      {error && <Text style={styles.errorText}>{error}</Text>}

      {/* Modal */}
      <Modal
        visible={visible}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setVisible(false)}
      >
        <SafeAreaView style={styles.modalContainer}>
          {/* Header */}
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={handleBack} style={styles.modalBackBtn}>
              <Ionicons
                name={level === "category" ? "close" : "arrow-back"}
                size={22}
                color="#1a1a2e"
              />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>{getTitle()}</Text>
            <View style={{ width: 32 }} />
          </View>

          {/* Breadcrumb in modal */}
          {level !== "category" && (
            <View style={styles.modalBreadcrumb}>
              <Text style={styles.modalBreadcrumbText}>
                {tempCategory?.category_name}
                {level === "minicategory" &&
                  ` → ${tempSubcategory?.sub_category_name}`}
              </Text>
            </View>
          )}

          {/* Search */}
          <View style={styles.searchContainer}>
            <Ionicons name="search-outline" size={18} color="#9ca3af" />
            <TextInput
              style={styles.searchInput}
              placeholder={`Search ${level === "category" ? "categories" : level === "subcategory" ? "subcategories" : "child categories"}...`}
              placeholderTextColor="#9ca3af"
              value={search}
              onChangeText={setSearch}
              autoCorrect={false}
            />
            {search.length > 0 && (
              <TouchableOpacity onPress={() => setSearch("")}>
                <Ionicons name="close-circle" size={18} color="#9ca3af" />
              </TouchableOpacity>
            )}
          </View>

          {/* List */}
          <FlatList
            data={filteredData}
            keyExtractor={(item: any) => String(item.id)}
            contentContainerStyle={styles.listContent}
            keyboardShouldPersistTaps="handled"
            ListEmptyComponent={
              <View style={styles.emptyState}>
                <Ionicons name="search" size={32} color="#d1d5db" />
                <Text style={styles.emptyText}>No results found</Text>
              </View>
            }
            renderItem={({ item }: { item: any }) => {
              const label = getItemLabel(item);
              const hasChildren =
                (level === "category" &&
                  item.subcategories?.length > 0) ||
                (level === "subcategory" &&
                  item.minicategories?.length > 0);

              return (
                <TouchableOpacity
                  style={styles.listItem}
                  onPress={() => handleItemPress(item)}
                  activeOpacity={0.6}
                >
                  <Text style={styles.listItemText}>{label}</Text>
                  {hasChildren && (
                    <View style={styles.listItemRight}>
                      <Text style={styles.listItemSub}>
                        {level === "category"
                          ? `${item.subcategories.length} sub`
                          : `${item.minicategories.length} child`}
                      </Text>
                      <Ionicons
                        name="chevron-forward"
                        size={16}
                        color="#9ca3af"
                      />
                    </View>
                  )}
                  {!hasChildren && (
                    <Ionicons
                      name="add-circle-outline"
                      size={20}
                      color={BRAND_PRIMARY}
                    />
                  )}
                </TouchableOpacity>
              );
            }}
          />

          {/* Skip subcategory option */}
          {level === "subcategory" && (
            <TouchableOpacity
              style={styles.skipBtn}
              onPress={() => {
                onChange({
                  categoryId: tempCategory!.id,
                  categoryName: tempCategory!.category_name,
                  subcategoryId: null,
                  subcategoryName: "",
                  minicategoryId: null,
                  minicategoryName: "",
                });
                setVisible(false);
              }}
            >
              <Text style={styles.skipBtnText}>
                Skip — use "{tempCategory?.category_name}" only
              </Text>
            </TouchableOpacity>
          )}

          {level === "minicategory" && (
            <TouchableOpacity
              style={styles.skipBtn}
              onPress={() => {
                onChange({
                  categoryId: tempCategory!.id,
                  categoryName: tempCategory!.category_name,
                  subcategoryId: tempSubcategory!.id,
                  subcategoryName: tempSubcategory!.sub_category_name,
                  minicategoryId: null,
                  minicategoryName: "",
                });
                setVisible(false);
              }}
            >
              <Text style={styles.skipBtnText}>
                Skip — use "{tempSubcategory?.sub_category_name}" only
              </Text>
            </TouchableOpacity>
          )}
        </SafeAreaView>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  outerContainer: {
    marginBottom: 16,
  },
  label: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 6,
    marginLeft: 2,
  },
  required: {
    color: "#EF4444",
    fontWeight: "400",
  },
  pickerButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    backgroundColor: "#f9fafb",
    borderWidth: 1.5,
    borderColor: "#e5e7eb",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 13,
    minHeight: 48,
  },
  pickerButtonError: {
    borderColor: "#EF4444",
    backgroundColor: "#FEF2F2",
  },
  pickerPlaceholder: {
    fontSize: 14,
    color: "#9ca3af",
  },
  breadcrumbRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    alignItems: "center",
    flex: 1,
    gap: 2,
  },
  breadcrumbChip: {
    flexDirection: "row",
    alignItems: "center",
  },
  breadcrumbText: {
    fontSize: 13,
    fontWeight: "500",
    color: "#1a1a2e",
  },
  errorText: {
    fontSize: 11,
    color: "#EF4444",
    marginTop: 4,
    marginLeft: 2,
  },

  /* Modal */
  modalContainer: {
    flex: 1,
    backgroundColor: "#fff",
  },
  modalHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  modalBackBtn: {
    width: 32,
    height: 32,
    alignItems: "center",
    justifyContent: "center",
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: "600",
    color: "#1a1a2e",
  },
  modalBreadcrumb: {
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  modalBreadcrumbText: {
    fontSize: 12,
    fontWeight: "500",
    color: BRAND_PRIMARY,
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f9fafb",
    marginHorizontal: 16,
    marginVertical: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#e5e7eb",
    gap: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: "#1a1a2e",
  },
  listContent: {
    paddingHorizontal: 16,
    paddingBottom: 20,
  },
  listItem: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingVertical: 14,
    paddingHorizontal: 4,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  listItemText: {
    fontSize: 14,
    color: "#1a1a2e",
    fontWeight: "500",
    flex: 1,
  },
  listItemRight: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  listItemSub: {
    fontSize: 11,
    color: "#9ca3af",
  },
  emptyState: {
    alignItems: "center",
    paddingVertical: 40,
    gap: 8,
  },
  emptyText: {
    fontSize: 14,
    color: "#9ca3af",
  },
  skipBtn: {
    marginHorizontal: 16,
    marginBottom: Platform.OS === "ios" ? 16 : 20,
    paddingVertical: 12,
    borderRadius: 10,
    backgroundColor: "#f3f4f6",
    alignItems: "center",
  },
  skipBtnText: {
    fontSize: 13,
    fontWeight: "500",
    color: "#6b7280",
  },
});
