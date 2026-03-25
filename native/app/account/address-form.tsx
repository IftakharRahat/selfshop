import { useState, useEffect } from "react";
import {
  View,
  ScrollView,
  TextInput,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Alert,
  Switch,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

export default function AddressFormScreen() {
  const { addressId } = useLocalSearchParams<{ addressId?: string }>();
  const isEditing = !!addressId;
  const queryClient = useQueryClient();

  const [label, setLabel] = useState("");
  const [recipientName, setRecipientName] = useState("");
  const [phone, setPhone] = useState("");
  const [addressText, setAddressText] = useState("");
  const [city, setCity] = useState("");
  const [area, setArea] = useState("");
  const [postalCode, setPostalCode] = useState("");
  const [isDefault, setIsDefault] = useState(false);

  // Load existing address for editing
  const addressesQuery = useQuery({
    queryKey: ["addresses"],
    queryFn: async () => {
      const { data } = await apiClient.get("/addresses");
      return data?.data ?? data ?? { addresses: [] };
    },
    enabled: isEditing,
  });

  useEffect(() => {
    if (isEditing && addressesQuery.data) {
      const addresses = addressesQuery.data.addresses ?? [];
      const addr = addresses.find(
        (a: any) => a.id === Number(addressId),
      );
      if (addr) {
        setLabel(addr.label);
        setRecipientName(addr.recipientName ?? addr.recipient_name ?? "");
        setPhone(addr.phone);
        setAddressText(addr.address);
        setCity(addr.city);
        setArea(addr.area ?? "");
        setPostalCode(addr.postalCode ?? addr.postal_code ?? "");
        setIsDefault(addr.isDefault ?? addr.is_default ?? false);
      }
    }
  }, [addressesQuery.data, addressId, isEditing]);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const payload = {
        label,
        recipient_name: recipientName,
        phone,
        address: addressText,
        city,
        area: area || undefined,
        postal_code: postalCode || undefined,
        is_default: isDefault,
      };

      if (isEditing) {
        return apiClient.put(`/addresses/${addressId}`, payload);
      }
      return apiClient.post("/addresses", payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["addresses"] });
      router.back();
    },
    onError: (err: any) => {
      Alert.alert("Error", err?.response?.data?.message || "Failed to save address");
    },
  });

  const isFormValid =
    label.trim().length > 0 &&
    recipientName.trim().length >= 2 &&
    phone.trim().length >= 10 &&
    addressText.trim().length >= 5 &&
    city.trim().length >= 2;

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: isEditing ? "Edit Address" : "Add Address",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.form}>
          {/* Label */}
          <View style={styles.labelPicker}>
            {["Home", "Office", "Other"].map((l) => (
              <Pressable
                key={l}
                style={[
                  styles.labelChip,
                  label === l && styles.labelChipActive,
                ]}
                onPress={() => setLabel(l)}
              >
                <Text
                  fontSize="$3"
                  fontWeight={label === l ? "bold" : "400"}
                  color={label === l ? "#fff" : "#1A1A2E"}
                >
                  {l}
                </Text>
              </Pressable>
            ))}
          </View>

          <InputField
            label="Recipient Name"
            value={recipientName}
            onChangeText={setRecipientName}
            placeholder="Full name"
          />

          <InputField
            label="Phone Number"
            value={phone}
            onChangeText={setPhone}
            placeholder="01XXXXXXXXX"
            keyboardType="phone-pad"
          />

          <InputField
            label="Address"
            value={addressText}
            onChangeText={setAddressText}
            placeholder="House, Road, Area"
            multiline
          />

          <InputField
            label="City"
            value={city}
            onChangeText={setCity}
            placeholder="e.g. Dhaka"
          />

          <InputField
            label="Area (Optional)"
            value={area}
            onChangeText={setArea}
            placeholder="e.g. Mirpur"
          />

          <InputField
            label="Postal Code (Optional)"
            value={postalCode}
            onChangeText={setPostalCode}
            placeholder="e.g. 1216"
            keyboardType="numeric"
          />

          {/* Default Toggle */}
          <View style={styles.toggleRow}>
            <Text fontSize="$4" color="#1A1A2E">
              Set as default address
            </Text>
            <Switch
              value={isDefault}
              onValueChange={setIsDefault}
              trackColor={{ false: "#D8D8D8", true: "#FCE7F3" }}
              thumbColor={isDefault ? "#E5005F" : "#fff"}
            />
          </View>

          {/* Save Button */}
          <Pressable
            style={({ pressed }) => [
              styles.saveButton,
              !isFormValid && styles.saveButtonDisabled,
              pressed && isFormValid && { opacity: 0.85 },
            ]}
            onPress={() => saveMutation.mutate()}
            disabled={!isFormValid || saveMutation.isPending}
          >
            {saveMutation.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                {isEditing ? "Update Address" : "Save Address"}
              </Text>
            )}
          </Pressable>
        </View>

        <View style={{ height: 30 }} />
      </ScrollView>
    </>
  );
}

function InputField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  multiline,
}: {
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder: string;
  keyboardType?: "default" | "phone-pad" | "numeric";
  multiline?: boolean;
}) {
  return (
    <View style={styles.inputGroup}>
      <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
        {label}
      </Text>
      <TextInput
        style={[styles.input, multiline && styles.inputMultiline]}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#C7C7CC"
        keyboardType={keyboardType}
        multiline={multiline}
        numberOfLines={multiline ? 3 : 1}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  form: { padding: 20, gap: 16 },
  labelPicker: {
    flexDirection: "row",
    gap: 10,
  },
  labelChip: {
    paddingVertical: 8,
    paddingHorizontal: 20,
    borderRadius: 20,
    backgroundColor: "#F5F5F5",
    borderWidth: 1,
    borderColor: "#F0F0F0",
  },
  labelChipActive: {
    backgroundColor: "#E5005F",
    borderColor: "#E5005F",
  },
  inputGroup: {},
  input: {
    borderWidth: 1,
    borderColor: "#E8E8E8",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: "#1A1A2E",
    fontFamily: "Inter",
    backgroundColor: "#FAFAFA",
  },
  inputMultiline: {
    minHeight: 80,
    textAlignVertical: "top",
  },
  toggleRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingVertical: 8,
  },
  saveButton: {
    backgroundColor: "#E5005F",
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: "center",
    marginTop: 8,
  },
  saveButtonDisabled: {
    opacity: 0.5,
  },
});
