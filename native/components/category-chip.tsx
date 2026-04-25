import { View, Image, StyleSheet, Pressable } from "react-native";
import { Text } from "tamagui";

interface CategoryChipProps {
  name: string;
  image: string;
  onPress?: () => void;
}

export function CategoryChip({ name, image, onPress }: CategoryChipProps) {
  return (
    <Pressable
      style={({ pressed }) => [styles.container, pressed && { opacity: 0.7 }]}
      onPress={onPress}
    >
      <View style={styles.imageWrapper}>
        <Image source={{ uri: image }} style={styles.image} resizeMode="cover" />
      </View>
      <Text fontSize={10} color="#1A1A2E" textAlign="center" numberOfLines={2} style={{ lineHeight: 13, maxWidth: 60 }}>
        {name}
      </Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: "center",
    width: 64,
    gap: 4,
  },
  imageWrapper: {
    width: 48,
    height: 48,
    borderRadius: 12,
    backgroundColor: "#F5F5FA",
    overflow: "hidden",
  },
  image: {
    width: 48,
    height: 48,
  },
});
