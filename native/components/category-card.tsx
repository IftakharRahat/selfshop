import { View, Image, StyleSheet, Pressable, Dimensions } from "react-native";
import { Text } from "tamagui";

const SCREEN_WIDTH = Dimensions.get("window").width;
const CARD_GAP = 12;
const SECTION_PADDING = 20;
const CARD_WIDTH = (SCREEN_WIDTH - SECTION_PADDING * 2 - CARD_GAP) / 2;
const IMG_GAP = 4;
const IMG_SIZE = (CARD_WIDTH - 16 - IMG_GAP) / 2; // 16 = card padding (8*2)

interface CategoryCardProps {
  name: string;
  images: string[];
  productCount?: number;
  onPress?: () => void;
}

export function CategoryCard({
  name,
  images,
  productCount,
  onPress,
}: CategoryCardProps) {
  // Fill up to 4 image slots
  const gridImages = images.slice(0, 4);
  while (gridImages.length < 4) {
    gridImages.push("");
  }

  return (
    <Pressable
      style={({ pressed }) => [styles.card, pressed && { opacity: 0.9, transform: [{ scale: 0.98 }] }]}
      onPress={onPress}
    >
      {/* 2x2 Image Grid */}
      <View style={styles.imageGrid}>
        <View style={styles.imageRow}>
          <Image
            source={gridImages[0] ? { uri: gridImages[0] } : undefined}
            style={styles.gridImage}
            resizeMode="cover"
          />
          <Image
            source={gridImages[1] ? { uri: gridImages[1] } : undefined}
            style={styles.gridImage}
            resizeMode="cover"
          />
        </View>
        <View style={styles.imageRow}>
          <Image
            source={gridImages[2] ? { uri: gridImages[2] } : undefined}
            style={styles.gridImage}
            resizeMode="cover"
          />
          <Image
            source={gridImages[3] ? { uri: gridImages[3] } : undefined}
            style={styles.gridImage}
            resizeMode="cover"
          />
        </View>
      </View>

      {/* Footer */}
      <View style={styles.footer}>
        <Text
          fontSize="$4"
          fontWeight="bold"
          color="#1A1A2E"
          numberOfLines={1}
          style={{ flex: 1 }}
        >
          {name}
        </Text>
        {productCount != null && productCount > 0 && (
          <View style={styles.badge}>
            <Text fontSize={11} fontWeight="600" color="#555">
              {productCount}
            </Text>
          </View>
        )}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    width: CARD_WIDTH,
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 8,
    gap: 8,
    // Shadow
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 3,
  },
  imageGrid: {
    gap: IMG_GAP,
  },
  imageRow: {
    flexDirection: "row",
    gap: IMG_GAP,
  },
  gridImage: {
    width: IMG_SIZE,
    height: IMG_SIZE,
    borderRadius: 10,
    backgroundColor: "#F0F0F5",
  },
  footer: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 2,
  },
  badge: {
    backgroundColor: "#F0F0F5",
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 3,
    minWidth: 36,
    alignItems: "center",
  },
});
