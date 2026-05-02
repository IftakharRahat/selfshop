import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
  FlatList,
  Alert,
} from "react-native";
import * as ImagePicker from "expo-image-picker";
import { Ionicons } from "@expo/vector-icons";

const BRAND_PRIMARY = "#4f46e5";

interface ImageGalleryPickerProps {
  thumbnail: string | null;
  gallery: string[];
  existingThumbnail?: string | null;
  existingGallery?: string[];
  onThumbnailChange: (uri: string | null) => void;
  onGalleryChange: (uris: string[]) => void;
  maxImages?: number;
}

export default function ImageGalleryPicker({
  thumbnail,
  gallery,
  existingThumbnail,
  existingGallery = [],
  onThumbnailChange,
  onGalleryChange,
  maxImages = 10,
}: ImageGalleryPickerProps) {
  const pickThumbnail = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      onThumbnailChange(result.assets[0].uri);
    }
  };

  const pickGalleryImages = async () => {
    const remaining = maxImages - gallery.length;
    if (remaining <= 0) {
      Alert.alert("Limit Reached", `Maximum ${maxImages} gallery images allowed.`);
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.8,
      allowsMultipleSelection: true,
      selectionLimit: remaining,
    });
    if (!result.canceled && result.assets.length > 0) {
      const newUris = result.assets.map((a) => a.uri);
      onGalleryChange([...gallery, ...newUris]);
    }
  };

  const removeGalleryImage = (index: number) => {
    const updated = [...gallery];
    updated.splice(index, 1);
    onGalleryChange(updated);
  };

  const displayThumbnail = thumbnail || existingThumbnail;

  return (
    <View style={styles.container}>
      {/* Thumbnail Section */}
      <Text style={styles.sectionLabel}>
        Thumbnail Image <Text style={styles.required}>*</Text>
      </Text>
      <TouchableOpacity
        style={styles.thumbnailWrap}
        onPress={pickThumbnail}
        activeOpacity={0.7}
      >
        {displayThumbnail ? (
          <Image source={{ uri: displayThumbnail }} style={styles.thumbnailImage} />
        ) : (
          <View style={styles.thumbnailPlaceholder}>
            <Ionicons name="camera-outline" size={32} color="#9ca3af" />
            <Text style={styles.placeholderText}>Add thumbnail</Text>
            <Text style={styles.placeholderSub}>1:1 aspect ratio recommended</Text>
          </View>
        )}
        <View style={styles.editBadge}>
          <Ionicons name="camera" size={14} color="#fff" />
        </View>
        {displayThumbnail && (
          <View style={styles.thumbBadge}>
            <Text style={styles.thumbBadgeText}>Thumbnail</Text>
          </View>
        )}
      </TouchableOpacity>

      {/* Gallery Section */}
      <View style={styles.galleryHeader}>
        <Text style={styles.sectionLabel}>Gallery Images</Text>
        <Text style={styles.countText}>
          {gallery.length + existingGallery.length}/{maxImages}
        </Text>
      </View>

      <View style={styles.galleryGrid}>
        {/* Existing gallery images */}
        {existingGallery.map((uri, index) => (
          <View key={`existing-${index}`} style={styles.galleryItem}>
            <Image source={{ uri }} style={styles.galleryImage} />
            <View style={styles.existingBadge}>
              <Ionicons name="cloud-done" size={10} color="#059669" />
            </View>
          </View>
        ))}

        {/* New gallery images */}
        {gallery.map((uri, index) => (
          <View key={`new-${index}`} style={styles.galleryItem}>
            <Image source={{ uri }} style={styles.galleryImage} />
            <TouchableOpacity
              style={styles.removeBtn}
              onPress={() => removeGalleryImage(index)}
            >
              <Ionicons name="close" size={12} color="#fff" />
            </TouchableOpacity>
            <View style={styles.newBadge}>
              <Text style={styles.newBadgeText}>New</Text>
            </View>
          </View>
        ))}

        {/* Add more tile */}
        {gallery.length + existingGallery.length < maxImages && (
          <TouchableOpacity
            style={styles.addTile}
            onPress={pickGalleryImages}
            activeOpacity={0.7}
          >
            <Ionicons name="add" size={28} color={BRAND_PRIMARY} />
            <Text style={styles.addTileText}>Add</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

const TILE_SIZE = 90;

const styles = StyleSheet.create({
  container: {},
  sectionLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 8,
    marginLeft: 2,
  },
  required: {
    color: "#EF4444",
    fontWeight: "400",
  },

  /* Thumbnail */
  thumbnailWrap: {
    width: "100%",
    height: 200,
    borderRadius: 14,
    overflow: "hidden",
    backgroundColor: "#f3f4f6",
    marginBottom: 20,
    position: "relative",
    borderWidth: 2,
    borderColor: "#e5e7eb",
    borderStyle: "dashed",
  },
  thumbnailImage: {
    width: "100%",
    height: "100%",
    resizeMode: "cover",
  },
  thumbnailPlaceholder: {
    width: "100%",
    height: "100%",
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
  },
  placeholderText: {
    fontSize: 14,
    fontWeight: "600",
    color: "#6b7280",
  },
  placeholderSub: {
    fontSize: 11,
    color: "#9ca3af",
  },
  editBadge: {
    position: "absolute",
    bottom: 10,
    right: 10,
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: BRAND_PRIMARY,
    alignItems: "center",
    justifyContent: "center",
    elevation: 4,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 4,
  },
  thumbBadge: {
    position: "absolute",
    top: 8,
    left: 8,
    backgroundColor: "rgba(0,0,0,0.6)",
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  thumbBadgeText: {
    fontSize: 10,
    fontWeight: "600",
    color: "#fff",
  },

  /* Gallery */
  galleryHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  countText: {
    fontSize: 12,
    color: "#6b7280",
    fontWeight: "500",
  },
  galleryGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  galleryItem: {
    width: TILE_SIZE,
    height: TILE_SIZE,
    borderRadius: 10,
    overflow: "hidden",
    position: "relative",
    backgroundColor: "#f3f4f6",
  },
  galleryImage: {
    width: "100%",
    height: "100%",
    resizeMode: "cover",
  },
  removeBtn: {
    position: "absolute",
    top: 4,
    right: 4,
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: "rgba(239,68,68,0.9)",
    alignItems: "center",
    justifyContent: "center",
  },
  existingBadge: {
    position: "absolute",
    bottom: 4,
    right: 4,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: "#D1FAE5",
    alignItems: "center",
    justifyContent: "center",
  },
  newBadge: {
    position: "absolute",
    bottom: 4,
    left: 4,
    backgroundColor: BRAND_PRIMARY,
    borderRadius: 4,
    paddingHorizontal: 5,
    paddingVertical: 1,
  },
  newBadgeText: {
    fontSize: 8,
    fontWeight: "700",
    color: "#fff",
  },
  addTile: {
    width: TILE_SIZE,
    height: TILE_SIZE,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: "#e5e7eb",
    borderStyle: "dashed",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#fafafa",
  },
  addTileText: {
    fontSize: 11,
    color: BRAND_PRIMARY,
    fontWeight: "600",
    marginTop: 2,
  },
});
