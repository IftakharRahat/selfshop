import { View, Image, StyleSheet, Pressable, Dimensions } from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const CARD_WIDTH = (Dimensions.get("window").width - 48 - 12) / 2;

interface ProductCardProps {
  name: string;
  price: string;
  image: string;
  slug?: string;
  category?: string;
  onPress?: () => void;
  variant?: "horizontal" | "grid";
}

export function ProductCard({
  name,
  price,
  image,
  slug,
  category,
  onPress,
  variant = "grid",
}: ProductCardProps) {
  const isHorizontal = variant === "horizontal";
  const { isActive: isResellerActive, isLoading } = useIsActiveReseller();

  const handlePress = () => {
    if (onPress) {
      onPress();
    } else if (slug) {
      router.push({ pathname: "/product-detail", params: { slug } });
    }
  };

  return (
    <Pressable
      style={({ pressed }) => [
        isHorizontal ? styles.horizontalCard : styles.gridCard,
        pressed && { opacity: 0.85 },
      ]}
      onPress={handlePress}
    >
      <Image
        source={{ uri: image }}
        style={isHorizontal ? styles.horizontalImage : styles.gridImage}
        resizeMode="cover"
      />
      <View style={styles.info}>
        {category && (
          <Text fontSize="$1" color="#F5A623" fontWeight="600" numberOfLines={1}>
            {category}
          </Text>
        )}
        <Text fontSize={13} color="#1A1A2E" fontWeight="600" numberOfLines={2} lineHeight={17}>
          {name}
        </Text>

        {isResellerActive ? (
          <Text fontSize="$4" color="#1A1A2E" fontWeight="bold">
            ৳{price}
          </Text>
        ) : (
          <View style={styles.lockedRow}>
            <Text fontSize="$4" color="#999" fontWeight="bold">***</Text>
            <View style={styles.lockBadge}>
              <Ionicons name="lock-closed" size={11} color="#E5005F" />
              <Text fontSize={11} fontWeight="700" color="#E5005F">Login</Text>
            </View>
          </View>
        )}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  horizontalCard: {
    width: 160,
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    overflow: "hidden",
  },
  gridCard: {
    width: CARD_WIDTH,
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    overflow: "hidden",
  },
  horizontalImage: {
    width: 160,
    height: 120,
    backgroundColor: "#F8F8F8",
  },
  gridImage: {
    width: "100%",
    height: CARD_WIDTH * 0.85,
    backgroundColor: "#F8F8F8",
  },
  info: {
    padding: 10,
    gap: 2,
    flex: 1,
  },
  lockedRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: "auto",
    paddingTop: 4,
  },
  lockBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 3,
    backgroundColor: "#FFF0F5",
    borderRadius: 4,
    paddingHorizontal: 6,
    paddingVertical: 2,
  },
});
