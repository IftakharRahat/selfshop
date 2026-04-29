import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
  RefreshControl,
  Switch,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND, CARD_SHADOW } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { SubScreenSkeleton } from "@/components/skeleton";

interface ShippingMethod {
  id: number;
  name: string;
  type: "flat" | "weight" | "zone";
  rate: number;
  min_order_amount?: number | null;
  max_order_amount?: number | null;
  per_kg_rate?: number | null;
  description?: string | null;
  is_default: boolean;
  is_active: boolean;
}

const TYPE_LABELS = { flat: "Flat Rate", weight: "Per KG", zone: "Zone-based" };

export default function ShippingScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const [showForm, setShowForm] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const [name, setName] = useState("");
  const [type, setType] = useState<"flat" | "weight" | "zone">("flat");
  const [rate, setRate] = useState("");
  const [perKgRate, setPerKgRate] = useState("");
  const [description, setDescription] = useState("");

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-shipping-methods"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/shipping-methods");
      return data?.data?.shipping_methods as ShippingMethod[];
    },
  });

  const saveMutation = useMutation({
    mutationFn: async () => {
      const body: Record<string, any> = { name, type, rate: Number(rate) };
      if (type === "weight" && perKgRate) body.per_kg_rate = Number(perKgRate);
      if (description) body.description = description;
      if (editId) {
        await apiClient.put(`/vendor/shipping-methods/${editId}`, body);
      } else {
        await apiClient.post("/vendor/shipping-methods", body);
      }
    },
    onSuccess: () => {
      toast.success(editId ? "Method updated" : "Method created");
      resetForm();
      queryClient.invalidateQueries({ queryKey: ["vendor-shipping-methods"] });
    },
    onError: () => toast.error("Failed to save"),
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/vendor/shipping-methods/${id}`);
    },
    onSuccess: () => {
      toast.success("Method deleted");
      queryClient.invalidateQueries({ queryKey: ["vendor-shipping-methods"] });
    },
  });

  const resetForm = () => {
    setShowForm(false);
    setEditId(null);
    setName(""); setType("flat"); setRate(""); setPerKgRate(""); setDescription("");
  };

  const startEdit = (m: ShippingMethod) => {
    setEditId(m.id);
    setName(m.name);
    setType(m.type);
    setRate(String(m.rate));
    setPerKgRate(m.per_kg_rate ? String(m.per_kg_rate) : "");
    setDescription(m.description ?? "");
    setShowForm(true);
  };

  const methods = data ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Shipping Methods</Text>
        <TouchableOpacity onPress={() => { resetForm(); setShowForm(!showForm); }}>
          <Ionicons name={showForm ? "close-circle" : "add-circle"} size={24} color={BRAND.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
      >
        {showForm && (
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>{editId ? "Edit Method" : "New Method"}</Text>
            <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Method name" placeholderTextColor="#9ca3af" />
            <View style={styles.chipRow}>
              {(Object.entries(TYPE_LABELS) as [keyof typeof TYPE_LABELS, string][]).map(([k, v]) => (
                <TouchableOpacity key={k} style={[styles.chip, type === k && styles.chipActive]} onPress={() => setType(k)}>
                  <Text style={[styles.chipText, type === k && styles.chipTextActive]}>{v}</Text>
                </TouchableOpacity>
              ))}
            </View>
            <View style={styles.row}>
              <TextInput style={[styles.input, { flex: 1 }]} value={rate} onChangeText={setRate} placeholder="Rate (৳)" keyboardType="numeric" placeholderTextColor="#9ca3af" />
              {type === "weight" && (
                <TextInput style={[styles.input, { flex: 1 }]} value={perKgRate} onChangeText={setPerKgRate} placeholder="Per KG rate" keyboardType="numeric" placeholderTextColor="#9ca3af" />
              )}
            </View>
            <TextInput style={styles.input} value={description} onChangeText={setDescription} placeholder="Description (optional)" placeholderTextColor="#9ca3af" />
            <TouchableOpacity style={[styles.saveBtn, saveMutation.isPending && { opacity: 0.6 }]} onPress={() => saveMutation.mutate()} disabled={saveMutation.isPending || !name || !rate}>
              <Text style={styles.saveBtnText}>{editId ? "Update" : "Create"}</Text>
            </TouchableOpacity>
          </View>
        )}

        {isLoading ? (
          <SubScreenSkeleton />
        ) : methods.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="boat-outline" size={48} color="#d1d5db" />
            <Text style={styles.emptyText}>No shipping methods</Text>
          </View>
        ) : (
          methods.map((m) => (
            <View key={m.id} style={styles.methodCard}>
              <View style={styles.methodHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.methodName}>{m.name}</Text>
                  <Text style={styles.methodType}>{TYPE_LABELS[m.type]} · ৳{m.rate}</Text>
                  {m.per_kg_rate ? <Text style={styles.methodType}>+ ৳{m.per_kg_rate}/kg</Text> : null}
                  {m.description ? <Text style={styles.methodDesc}>{m.description}</Text> : null}
                </View>
                <View style={styles.methodActions}>
                  {m.is_default && <View style={styles.defaultBadge}><Text style={styles.defaultText}>Default</Text></View>}
                  <TouchableOpacity onPress={() => startEdit(m)}>
                    <Ionicons name="create-outline" size={18} color={BRAND.primary} />
                  </TouchableOpacity>
                  <TouchableOpacity onPress={() => Alert.alert("Delete", "Remove this method?", [
                    { text: "Cancel", style: "cancel" },
                    { text: "Delete", style: "destructive", onPress: () => deleteMutation.mutate(m.id) },
                  ])}>
                    <Ionicons name="trash-outline" size={18} color="#ef4444" />
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          ))
        )}
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  formCard: { backgroundColor: "#fff", borderRadius: 14, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: BRAND.primaryLight, gap: 10 },
  formTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e" },
  row: { flexDirection: "row", gap: 8 },
  input: { backgroundColor: "#f9fafb", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: "#1a1a2e" },
  chipRow: { flexDirection: "row", gap: 6 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, backgroundColor: "#f3f4f6", borderWidth: 1, borderColor: "#e5e7eb" },
  chipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  chipText: { fontSize: 11, fontWeight: "500", color: "#6b7280" },
  chipTextActive: { color: "#fff" },
  saveBtn: { backgroundColor: BRAND.primary, borderRadius: 8, paddingVertical: 11, alignItems: "center" },
  saveBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  emptyState: { alignItems: "center", paddingVertical: 60, gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  methodCard: { backgroundColor: "#fff", borderRadius: 12, padding: 14, marginBottom: 8, ...CARD_SHADOW },
  methodHeader: { flexDirection: "row", justifyContent: "space-between" },
  methodName: { fontSize: 14, fontWeight: "600", color: "#1a1a2e" },
  methodType: { fontSize: 11, color: "#6b7280", marginTop: 2 },
  methodDesc: { fontSize: 11, color: "#9ca3af", marginTop: 4 },
  methodActions: { flexDirection: "row", alignItems: "center", gap: 10 },
  defaultBadge: { backgroundColor: "#D1FAE5", paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  defaultText: { fontSize: 9, fontWeight: "600", color: "#065F46" },
});
