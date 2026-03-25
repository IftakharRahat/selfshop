import { useState, useEffect } from "react";
import {
  View,
  ScrollView,
  TextInput,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Alert,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

export default function EditProfileScreen() {
  const queryClient = useQueryClient();

  const profileQuery = useQuery({
    queryKey: ["profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user");
      return data?.data ?? data;
    },
  });
  const profile = profileQuery.data;

  const [ownerName, setOwnerName] = useState("");
  const [phoneNumber, setPhoneNumber] = useState("");
  const [address, setAddress] = useState("");
  const [whatsapp, setWhatsapp] = useState("");
  const [facebook, setFacebook] = useState("");

  useEffect(() => {
    if (profile) {
      setOwnerName(profile.ownerName ?? profile.owner_name ?? profile.name ?? "");
      setPhoneNumber(profile.phoneNumber ?? profile.phone_number ?? profile.phone ?? "");
      setAddress(profile.address ?? "");
      setWhatsapp(profile.whatsapp ?? "");
      setFacebook(profile.facebook ?? "");
    }
  }, [profile]);

  const updateMutation = useMutation({
    mutationFn: () =>
      apiClient.put("/user/profile", {
        name: ownerName,
        phone: phoneNumber || null,
        address: address || null,
        whatsapp: whatsapp || null,
        facebook: facebook || null,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["profile"] });
      Alert.alert("Success", "Profile updated");
      router.back();
    },
    onError: (err: any) => {
      Alert.alert("Error", err?.response?.data?.message || "Failed to update profile");
    },
  });

  const isFormValid = ownerName.trim().length >= 1;

  if (profileQuery.isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#E5005F" />
      </View>
    );
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Edit Profile",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        {/* Email (read-only) */}
        <View style={styles.infoCard}>
          <Text fontSize="$2" color="#8E8E93">
            Email
          </Text>
          <Text fontSize="$4" color="#1A1A2E" mt="$1">
            {profile?.email}
          </Text>
        </View>

        <View style={styles.form}>
          <InputField
            label="Full Name"
            value={ownerName}
            onChangeText={setOwnerName}
            placeholder="Your name"
          />

          <InputField
            label="Phone Number"
            value={phoneNumber}
            onChangeText={setPhoneNumber}
            placeholder="01XXXXXXXXX"
            keyboardType="phone-pad"
          />

          <InputField
            label="Address"
            value={address}
            onChangeText={setAddress}
            placeholder="Your address"
            multiline
          />

          <InputField
            label="WhatsApp"
            value={whatsapp}
            onChangeText={setWhatsapp}
            placeholder="WhatsApp number"
            keyboardType="phone-pad"
          />

          <InputField
            label="Facebook (Optional)"
            value={facebook}
            onChangeText={setFacebook}
            placeholder="https://facebook.com/yourprofile"
          />

          {/* Save Button */}
          <Pressable
            style={({ pressed }) => [
              styles.saveButton,
              !isFormValid && styles.saveButtonDisabled,
              pressed && isFormValid && { opacity: 0.85 },
            ]}
            onPress={() => updateMutation.mutate()}
            disabled={!isFormValid || updateMutation.isPending}
          >
            {updateMutation.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                Save Changes
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
  keyboardType?: "default" | "phone-pad";
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
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
  },
  infoCard: {
    backgroundColor: "#F8F8F8",
    padding: 16,
    marginHorizontal: 20,
    marginTop: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#F0F0F0",
  },
  form: { padding: 20, gap: 16 },
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
