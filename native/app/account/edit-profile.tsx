import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Image,
  Platform,
  Pressable,
  StyleSheet,
  TextInput,
  View,
  type KeyboardTypeOptions,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Stack, router } from "expo-router";
import * as ImagePicker from "expo-image-picker";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Text } from "tamagui";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
const IMAGE_UPLOAD_TIMEOUT_MS = 60000;
const SUPPORTED_UPLOAD_EXTENSIONS = new Set(["jpg", "jpeg", "png", "webp", "gif"]);
const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

type UploadKind = "profile" | "nid";

function profileFromPayload(payload: any) {
  return payload?.profile ?? payload?.data?.profile ?? payload?.data?.data?.profile ?? payload;
}

function textValue(value: unknown): string {
  if (value === null || value === undefined) return "";
  return typeof value === "string" ? value : String(value);
}

function resolveImageUrl(path?: unknown): string | null {
  if (typeof path !== "string") return null;
  if (path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;

  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) {
    return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  }
  if (clean.startsWith("storage/") || clean.startsWith("images/")) {
    return `${IMAGE_BASE}/${clean}`;
  }
  return `${IMAGE_BASE}/storage/${clean}`;
}

function normalizeDob(value?: string | null): string {
  if (!value) return "";
  const text = String(value);
  return DATE_PATTERN.test(text.slice(0, 10)) ? text.slice(0, 10) : text;
}

function isValidDateString(value: string): boolean {
  if (!value) return true;
  if (!DATE_PATTERN.test(value)) return false;

  const [year, month, day] = value.split("-").map(Number);
  const date = new Date(Date.UTC(year, month - 1, day));
  return (
    date.getUTCFullYear() === year &&
    date.getUTCMonth() === month - 1 &&
    date.getUTCDate() === day
  );
}

function getExtensionFromName(value?: string | null): string | null {
  if (!value) return null;
  const clean = value.split("?")[0].split("#")[0];
  const match = clean.match(/\.([a-zA-Z0-9]+)$/);
  const extension = match ? match[1].toLowerCase() : null;
  return extension && SUPPORTED_UPLOAD_EXTENSIONS.has(extension) ? extension : null;
}

function getExtensionFromMime(mimeType?: string | null): string | null {
  if (!mimeType) return null;
  const normalized = mimeType.toLowerCase();
  if (normalized === "image/jpeg" || normalized === "image/jpg") return "jpg";
  if (normalized === "image/png") return "png";
  if (normalized === "image/webp") return "webp";
  if (normalized === "image/gif") return "gif";
  return null;
}

function getMimeFromExtension(extension?: string | null): string {
  switch ((extension ?? "").toLowerCase()) {
    case "jpg":
    case "jpeg":
      return "image/jpeg";
    case "png":
      return "image/png";
    case "webp":
      return "image/webp";
    case "gif":
      return "image/gif";
    default:
      return "image/jpeg";
  }
}

function makeUploadFile(asset: ImagePicker.ImagePickerAsset, kind: UploadKind) {
  const extension =
    getExtensionFromMime(asset.mimeType) ??
    getExtensionFromName(asset.fileName) ??
    getExtensionFromName(asset.uri) ??
    "jpg";
  const mimeType = asset.mimeType || getMimeFromExtension(extension);
  const fallbackName = `${kind}_${Date.now()}.${extension}`;
  const sourceName = asset.fileName?.replace(/[^\w.-]/g, "_") || fallbackName;
  const finalName = sourceName.toLowerCase().endsWith(`.${extension}`)
    ? sourceName
    : `${sourceName.replace(/\.[^.]+$/, "")}.${extension}`;

  return {
    uri: asset.uri,
    type: mimeType,
    name: finalName,
  };
}

function firstErrorMessage(err: any): string {
  const errors = err?.response?.data?.errors as Record<string, string[]> | undefined;
  const firstValidationError = errors ? (Object.values(errors).flat().find(Boolean) as string | undefined) : undefined;
  return firstValidationError || err?.response?.data?.message || "Failed to update profile";
}

export default function EditProfileScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();

  const profileQuery = useQuery({
    queryKey: ["user-profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-profile");
      return data?.data ?? data;
    },
  });
  const profile = profileFromPayload(profileQuery.data);

  const [name, setName] = useState("");
  const [dob, setDob] = useState("");
  const [address, setAddress] = useState("");
  const [shopName, setShopName] = useState("");
  const [profileImage, setProfileImage] = useState<ImagePicker.ImagePickerAsset | null>(null);
  const [nidImage, setNidImage] = useState<ImagePicker.ImagePickerAsset | null>(null);

  useEffect(() => {
    if (!profile) return;

    setName(textValue(profile.name ?? profile.ownerName ?? profile.owner_name));
    setDob(normalizeDob(profile.dob));
    setAddress(textValue(profile.address));
    setShopName(textValue(profile.shop_name ?? profile.shopName));
    setProfileImage(null);
    setNidImage(null);
  }, [profile]);

  const isDobValid = isValidDateString(dob.trim());
  const isFormValid = name.trim().length >= 1 && isDobValid;
  const profileImageUrl = resolveImageUrl(profile?.profile);
  const nidImageUrl = resolveImageUrl(profile?.nid);
  const bottomInset = Math.max(insets.bottom, 16);

  const updateMutation = useMutation({
    mutationFn: async () => {
      const formData = new FormData();
      formData.append("name", name.trim());
      formData.append("dob", dob.trim());
      formData.append("address", address);
      formData.append("shop_name", shopName);

      if (profileImage) {
        formData.append("profile", makeUploadFile(profileImage, "profile") as any);
      }
      if (nidImage) {
        formData.append("nid", makeUploadFile(nidImage, "nid") as any);
      }

      const { data } = await apiClient.post("/update-profile", formData, {
        headers: { "Content-Type": "multipart/form-data" },
        timeout: IMAGE_UPLOAD_TIMEOUT_MS,
      });

      if (data?.status === false) {
        const error: any = new Error(data?.message ?? "Failed to update profile");
        error.response = { data };
        throw error;
      }

      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["profile"] });
      queryClient.invalidateQueries({ queryKey: ["user-profile"] });
      showDialog({
        tone: "success",
        title: "Profile updated",
        message: "Your profile changes have been saved.",
        actions: [{ label: "OK", onPress: () => router.back() }],
      });
    },
    onError: (err: any) => {
      showDialog({
        tone: "error",
        title: "Could not update profile",
        message: firstErrorMessage(err),
      });
    },
  });

  const pickImage = async (kind: UploadKind) => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      showDialog({
        tone: "warning",
        title: "Photo access needed",
        message: "Please allow photo library access to upload an image.",
      });
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      preferredAssetRepresentationMode: ImagePicker.UIImagePickerPreferredAssetRepresentationMode.Compatible,
      quality: 0.85,
    });

    if (result.canceled || !result.assets[0]) return;

    const asset = result.assets[0];
    if (asset.type && asset.type !== "image") {
      showDialog({
        tone: "warning",
        title: "Choose an image",
        message: "Only image files can be uploaded.",
      });
      return;
    }

    if (asset.fileSize && asset.fileSize > MAX_IMAGE_SIZE) {
      showDialog({
        tone: "warning",
        title: "Image is too large",
        message: "Please choose an image under 5MB.",
      });
      return;
    }

    if (kind === "profile") {
      setProfileImage(asset);
      return;
    }
    setNidImage(asset);
  };

  const handleSubmit = () => {
    if (!name.trim()) {
      showDialog({
        tone: "warning",
        title: "Full name required",
        message: "Please enter your full name.",
      });
      return;
    }

    if (!isDobValid) {
      showDialog({
        tone: "warning",
        title: "Check date of birth",
        message: "Use the YYYY-MM-DD format.",
      });
      return;
    }

    updateMutation.mutate();
  };

  if (profileQuery.isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={ACCENT} />
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
      <KeyboardAwareScrollView
        style={styles.container}
        contentContainerStyle={[styles.scrollContent, { paddingBottom: bottomInset + 48 }]}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        bottomOffset={bottomInset + 24}
      >
        <View style={styles.form}>
          <InputField
            label="Full Name"
            value={name}
            onChangeText={setName}
            placeholder="Your name"
          />

          <InputField
            label="Date of Birth"
            value={dob}
            onChangeText={setDob}
            placeholder="YYYY-MM-DD"
            keyboardType="numbers-and-punctuation"
            maxLength={10}
            error={dob.trim().length > 0 && !isDobValid ? "Use YYYY-MM-DD" : undefined}
          />

          <InputField
            label="Address"
            value={address}
            onChangeText={setAddress}
            placeholder="Your address"
          />

          <InputField
            label="Shop Name"
            value={shopName}
            onChangeText={setShopName}
            placeholder="Your shop name"
          />

          <ImageUploadField
            label="Profile Image"
            selectedAsset={profileImage}
            existingImageUrl={profileImageUrl}
            onPress={() => pickImage("profile")}
          />

          <ImageUploadField
            label="NID Document"
            selectedAsset={nidImage}
            existingImageUrl={nidImageUrl}
            onPress={() => pickImage("nid")}
          />

          <Pressable
            style={({ pressed }) => [
              styles.saveButton,
              !isFormValid && styles.saveButtonDisabled,
              pressed && isFormValid && { opacity: 0.85 },
            ]}
            onPress={handleSubmit}
            disabled={!isFormValid || updateMutation.isPending}
          >
            {updateMutation.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                Update Profile
              </Text>
            )}
          </Pressable>
        </View>
      </KeyboardAwareScrollView>
      <AppDialog state={dialog} onClose={closeDialog} />
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
  maxLength,
  error,
}: {
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder: string;
  keyboardType?: KeyboardTypeOptions;
  multiline?: boolean;
  maxLength?: number;
  error?: string;
}) {
  return (
    <View style={styles.inputGroup}>
      <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
        {label}
      </Text>
      <TextInput
        style={[styles.input, multiline && styles.inputMultiline, error && styles.inputError]}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#C7C7CC"
        keyboardType={keyboardType}
        multiline={multiline}
        maxLength={maxLength}
        numberOfLines={multiline ? 3 : 1}
      />
      {error ? <Text style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

function ImageUploadField({
  label,
  selectedAsset,
  existingImageUrl,
  onPress,
}: {
  label: string;
  selectedAsset: ImagePicker.ImagePickerAsset | null;
  existingImageUrl: string | null;
  onPress: () => void;
}) {
  const previewUri = selectedAsset?.uri ?? existingImageUrl;
  const title = selectedAsset
    ? selectedAsset.fileName || "New image selected"
    : existingImageUrl
      ? "Current image"
      : "Choose image";

  return (
    <View style={styles.inputGroup}>
      <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
        {label}
      </Text>
      <Pressable
        style={({ pressed }) => [styles.uploadControl, pressed && { backgroundColor: "#F7F7F8" }]}
        onPress={onPress}
      >
        {previewUri ? (
          <Image source={{ uri: previewUri }} style={styles.uploadPreview} />
        ) : (
          <View style={styles.uploadPlaceholder}>
            <Ionicons name="image-outline" size={20} color={ACCENT} />
          </View>
        )}
        <View style={styles.uploadText}>
          <Text fontSize="$3" fontWeight="600" color="#1A1A2E" numberOfLines={1}>
            {title}
          </Text>
          <Text fontSize="$2" color="#8E8E93">
            JPG, PNG, WEBP or GIF up to 5MB
          </Text>
        </View>
        <Ionicons name="cloud-upload-outline" size={20} color="#8E8E93" />
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  scrollContent: {
    flexGrow: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
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
  inputError: {
    borderColor: "#EF4444",
    backgroundColor: "#FFF7F7",
  },
  fieldError: {
    color: "#EF4444",
    fontSize: 12,
    marginTop: 6,
  },
  uploadControl: {
    minHeight: 76,
    borderWidth: 1,
    borderColor: "#E8E8E8",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "#FAFAFA",
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  uploadPreview: {
    width: 52,
    height: 52,
    borderRadius: 10,
    backgroundColor: "#F0F0F0",
  },
  uploadPlaceholder: {
    width: 52,
    height: 52,
    borderRadius: 10,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  uploadText: {
    flex: 1,
    gap: 2,
  },
  saveButton: {
    backgroundColor: ACCENT,
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: "center",
    marginTop: 8,
  },
  saveButtonDisabled: {
    opacity: 0.5,
  },
});
