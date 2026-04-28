import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  Alert,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";

const BRAND_PRIMARY = "#4f46e5";

/* ── Types ── */
export interface BulkTierRow {
  min_qty: string;
  max_qty: string;
  bulk_price: string;
}

export interface SizeRow {
  id: string;
  size_name: string;
  qty: string;
  price: string;
  bulkTiers: BulkTierRow[];
}

export interface VariantRow {
  id: string;
  title: string;
  color_name: string;
  color_code: string;
  sizes: SizeRow[];
}

interface VariantBuilderProps {
  variants: VariantRow[];
  onChange: (variants: VariantRow[]) => void;
}

function uid() {
  return Math.random().toString(36).substring(2, 9);
}

/* ── Main Component ── */
export default function VariantBuilder({ variants, onChange }: VariantBuilderProps) {
  // New variant form
  const [colorName, setColorName] = useState("");
  const [colorCode, setColorCode] = useState("");
  const [varTitle, setVarTitle] = useState("");

  // Per-variant size form
  const [sizeFormFor, setSizeFormFor] = useState<string | null>(null);
  const [szName, setSzName] = useState("");
  const [szPrice, setSzPrice] = useState("");
  const [szQty, setSzQty] = useState("");

  // Per-size bulk form
  const [bulkFormFor, setBulkFormFor] = useState<string | null>(null);
  const [btMin, setBtMin] = useState("");
  const [btMax, setBtMax] = useState("");
  const [btPrice, setBtPrice] = useState("");

  const addVariant = () => {
    if (!colorName.trim()) {
      Alert.alert("Required", "Color name is required.");
      return;
    }
    onChange([
      ...variants,
      { id: uid(), title: varTitle.trim() || colorName.trim(), color_name: colorName.trim(), color_code: colorCode.trim() || "#000000", sizes: [] },
    ]);
    setColorName("");
    setColorCode("");
    setVarTitle("");
  };

  const removeVariant = (idx: number) => {
    Alert.alert("Delete Variant", "Remove this color variant?", [
      { text: "Cancel", style: "cancel" },
      { text: "Delete", style: "destructive", onPress: () => onChange(variants.filter((_, i) => i !== idx)) },
    ]);
  };

  const addSize = (vIdx: number) => {
    if (!szName.trim()) { Alert.alert("Required", "Size name is required."); return; }
    if (!szPrice.trim()) { Alert.alert("Required", "Price is required."); return; }
    const next = [...variants];
    next[vIdx].sizes.push({ id: uid(), size_name: szName.trim(), price: szPrice.trim(), qty: szQty.trim() || "0", bulkTiers: [] });
    onChange(next);
    setSizeFormFor(null);
    setSzName(""); setSzPrice(""); setSzQty("");
  };

  const removeSize = (vIdx: number, sIdx: number) => {
    const next = [...variants];
    next[vIdx].sizes.splice(sIdx, 1);
    onChange(next);
  };

  const addBulkTier = (vIdx: number, sIdx: number) => {
    if (!btMin.trim() || !btPrice.trim()) { Alert.alert("Required", "Min qty and bulk price required."); return; }
    const next = [...variants];
    next[vIdx].sizes[sIdx].bulkTiers.push({ min_qty: btMin.trim(), max_qty: btMax.trim(), bulk_price: btPrice.trim() });
    onChange(next);
    setBulkFormFor(null);
    setBtMin(""); setBtMax(""); setBtPrice("");
  };

  const removeBulkTier = (vIdx: number, sIdx: number, btIdx: number) => {
    const next = [...variants];
    next[vIdx].sizes[sIdx].bulkTiers.splice(btIdx, 1);
    onChange(next);
  };

  return (
    <View>
      {/* ── Add Variant Form ── */}
      <View style={s.addCard}>
        <Text style={s.addCardTitle}>Add Color Variant</Text>
        <TextInput style={s.input} value={colorName} onChangeText={setColorName} placeholder="Color name (e.g. Red) *" placeholderTextColor="#9ca3af" />
        <View style={s.row}>
          <TextInput style={[s.input, { flex: 1 }]} value={varTitle} onChangeText={setVarTitle} placeholder="Title (optional)" placeholderTextColor="#9ca3af" />
          <TextInput style={[s.input, { width: 80 }]} value={colorCode} onChangeText={setColorCode} placeholder="#hex" placeholderTextColor="#9ca3af" />
        </View>
        <TouchableOpacity style={s.addBtn} onPress={addVariant} activeOpacity={0.8}>
          <Ionicons name="add-circle" size={16} color="#fff" />
          <Text style={s.addBtnText}>Add Color</Text>
        </TouchableOpacity>
      </View>

      {/* ── Variants List ── */}
      {variants.map((v, vIdx) => (
        <View key={v.id} style={s.variantCard}>
          {/* Header */}
          <View style={s.varHeader}>
            <View style={s.varTitleRow}>
              <View style={[s.colorDot, { backgroundColor: v.color_code || "#000" }]} />
              <View style={{ flex: 1 }}>
                <Text style={s.varName}>{v.color_name}</Text>
                {v.title !== v.color_name && <Text style={s.varSub}>{v.title}</Text>}
              </View>
            </View>
            <TouchableOpacity onPress={() => removeVariant(vIdx)} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
              <Ionicons name="trash-outline" size={18} color="#ef4444" />
            </TouchableOpacity>
          </View>

          {/* Sizes */}
          {v.sizes.length > 0 && (
            <View style={s.sizesWrap}>
              <Text style={s.sizesLabel}>SIZES</Text>
              {v.sizes.map((sz, sIdx) => (
                <View key={sz.id} style={s.sizeCard}>
                  <View style={s.sizeHeader}>
                    <View style={s.sizeInfo}>
                      <Text style={s.sizeName}>{sz.size_name}</Text>
                      <Text style={s.sizeDetail}>৳{sz.price} · Qty: {sz.qty}</Text>
                    </View>
                    <TouchableOpacity onPress={() => removeSize(vIdx, sIdx)}>
                      <Ionicons name="close-circle" size={16} color="#9ca3af" />
                    </TouchableOpacity>
                  </View>

                  {/* Bulk tiers */}
                  {sz.bulkTiers.length > 0 && (
                    <View style={s.bulkWrap}>
                      {sz.bulkTiers.map((bt, btIdx) => (
                        <View key={btIdx} style={s.bulkRow}>
                          <Text style={s.bulkText}>Qty {bt.min_qty}–{bt.max_qty || "∞"}: ৳{bt.bulk_price}</Text>
                          <TouchableOpacity onPress={() => removeBulkTier(vIdx, sIdx, btIdx)}>
                            <Ionicons name="close" size={12} color="#9ca3af" />
                          </TouchableOpacity>
                        </View>
                      ))}
                    </View>
                  )}

                  {/* Add Bulk Tier */}
                  {bulkFormFor === sz.id ? (
                    <View style={s.inlineForm}>
                      <View style={s.row}>
                        <TextInput style={[s.smInput, { flex: 1 }]} value={btMin} onChangeText={setBtMin} placeholder="Min" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                        <TextInput style={[s.smInput, { flex: 1 }]} value={btMax} onChangeText={setBtMax} placeholder="Max" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                        <TextInput style={[s.smInput, { flex: 1 }]} value={btPrice} onChangeText={setBtPrice} placeholder="Price" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                      </View>
                      <View style={s.row}>
                        <TouchableOpacity style={[s.smBtn, { flex: 1 }]} onPress={() => addBulkTier(vIdx, sIdx)}>
                          <Text style={s.smBtnText}>Add Tier</Text>
                        </TouchableOpacity>
                        <TouchableOpacity style={[s.smCancelBtn, { flex: 1 }]} onPress={() => setBulkFormFor(null)}>
                          <Text style={s.smCancelText}>Cancel</Text>
                        </TouchableOpacity>
                      </View>
                    </View>
                  ) : (
                    <TouchableOpacity style={s.addTierLink} onPress={() => { setBulkFormFor(sz.id); setBtMin(""); setBtMax(""); setBtPrice(""); }}>
                      <Ionicons name="layers-outline" size={12} color={BRAND_PRIMARY} />
                      <Text style={s.addTierText}>Add Bulk Tier</Text>
                    </TouchableOpacity>
                  )}
                </View>
              ))}
            </View>
          )}

          {/* Add Size */}
          {sizeFormFor === v.id ? (
            <View style={s.inlineForm}>
              <TextInput style={s.input} value={szName} onChangeText={setSzName} placeholder="Size name (e.g. S, 40, Free) *" placeholderTextColor="#9ca3af" />
              <View style={s.row}>
                <TextInput style={[s.input, { flex: 1 }]} value={szPrice} onChangeText={setSzPrice} placeholder="Price *" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                <TextInput style={[s.input, { flex: 1 }]} value={szQty} onChangeText={setSzQty} placeholder="Qty" keyboardType="numeric" placeholderTextColor="#9ca3af" />
              </View>
              <View style={s.row}>
                <TouchableOpacity style={[s.addBtn, { flex: 1 }]} onPress={() => addSize(vIdx)}>
                  <Text style={s.addBtnText}>Add Size</Text>
                </TouchableOpacity>
                <TouchableOpacity style={[s.cancelBtn, { flex: 1 }]} onPress={() => setSizeFormFor(null)}>
                  <Text style={s.cancelText}>Cancel</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <TouchableOpacity style={s.addSizeBtn} onPress={() => { setSizeFormFor(v.id); setSzName(""); setSzPrice(""); setSzQty(""); }}>
              <Ionicons name="add-circle-outline" size={14} color={BRAND_PRIMARY} />
              <Text style={s.addSizeText}>Add Size</Text>
            </TouchableOpacity>
          )}
        </View>
      ))}

      {variants.length === 0 && (
        <View style={s.emptyHint}>
          <Ionicons name="color-palette-outline" size={32} color="#d1d5db" />
          <Text style={s.emptyText}>No variants added yet</Text>
          <Text style={s.emptySub}>Add color variants with sizes and bulk pricing above</Text>
        </View>
      )}
    </View>
  );
}

/* ── Styles ── */
const s = StyleSheet.create({
  addCard: { backgroundColor: "#fff", borderRadius: 14, padding: 14, marginBottom: 12, borderWidth: 1, borderColor: "#E0E7FF", gap: 8 },
  addCardTitle: { fontSize: 14, fontWeight: "600", color: "#312E81", marginBottom: 2 },
  row: { flexDirection: "row", gap: 8 },
  input: { backgroundColor: "#f9fafb", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: "#1a1a2e" },
  smInput: { backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 6, paddingHorizontal: 8, paddingVertical: 7, fontSize: 11, color: "#1a1a2e" },
  addBtn: { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6, backgroundColor: BRAND_PRIMARY, borderRadius: 8, paddingVertical: 10 },
  addBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  cancelBtn: { alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6", borderRadius: 8, paddingVertical: 10 },
  cancelText: { fontSize: 13, fontWeight: "500", color: "#6b7280" },
  smBtn: { alignItems: "center", justifyContent: "center", backgroundColor: BRAND_PRIMARY, borderRadius: 6, paddingVertical: 7 },
  smBtnText: { fontSize: 11, fontWeight: "600", color: "#fff" },
  smCancelBtn: { alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6", borderRadius: 6, paddingVertical: 7 },
  smCancelText: { fontSize: 11, fontWeight: "500", color: "#6b7280" },

  variantCard: { backgroundColor: "#fff", borderRadius: 14, padding: 14, marginBottom: 10, borderWidth: 1, borderColor: "#f3f4f6" },
  varHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 6 },
  varTitleRow: { flexDirection: "row", alignItems: "center", gap: 10, flex: 1 },
  colorDot: { width: 22, height: 22, borderRadius: 11, borderWidth: 1, borderColor: "#e5e7eb" },
  varName: { fontSize: 15, fontWeight: "700", color: "#1a1a2e" },
  varSub: { fontSize: 11, color: "#9ca3af", marginTop: 1 },

  sizesWrap: { marginTop: 8, paddingTop: 8, borderTopWidth: 1, borderTopColor: "#f3f4f6" },
  sizesLabel: { fontSize: 10, fontWeight: "700", color: "#9ca3af", letterSpacing: 0.8, marginBottom: 6 },
  sizeCard: { backgroundColor: "#f9fafb", borderRadius: 8, padding: 10, marginBottom: 6, borderWidth: 1, borderColor: "#f3f4f6" },
  sizeHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  sizeInfo: { flex: 1 },
  sizeName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  sizeDetail: { fontSize: 11, color: "#6b7280", marginTop: 1 },

  bulkWrap: { marginTop: 6, marginLeft: 8, paddingLeft: 8, borderLeftWidth: 2, borderLeftColor: "#E0E7FF" },
  bulkRow: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingVertical: 3 },
  bulkText: { fontSize: 11, color: BRAND_PRIMARY, fontWeight: "500" },

  addTierLink: { flexDirection: "row", alignItems: "center", gap: 4, marginTop: 6 },
  addTierText: { fontSize: 10, fontWeight: "600", color: BRAND_PRIMARY },

  inlineForm: { marginTop: 8, backgroundColor: "#EEF2FF", borderRadius: 8, padding: 10, gap: 8 },

  addSizeBtn: { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 4, paddingVertical: 8, marginTop: 6, borderWidth: 1, borderColor: "#E0E7FF", borderStyle: "dashed", borderRadius: 8 },
  addSizeText: { fontSize: 12, fontWeight: "600", color: BRAND_PRIMARY },

  emptyHint: { alignItems: "center", paddingVertical: 30, gap: 6 },
  emptyText: { fontSize: 14, fontWeight: "600", color: "#9ca3af" },
  emptySub: { fontSize: 11, color: "#d1d5db", textAlign: "center", paddingHorizontal: 20 },
});
