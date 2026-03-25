import { View, TextInput, StyleSheet, Pressable } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { router } from "expo-router";

interface SearchBarProps {
  placeholder?: string;
  onPress?: () => void;
}

export function SearchBar({ placeholder = "Search products...", onPress }: SearchBarProps) {
  const handlePress = () => {
    if (onPress) {
      onPress();
    } else {
      router.push("/search");
    }
  };

  return (
    <Pressable onPress={handlePress} style={styles.container}>
      <Ionicons name="search-outline" size={18} color="#8E8E93" />
      <TextInput
        style={styles.input}
        placeholder={placeholder}
        placeholderTextColor="#C7C7CC"
        editable={false}
        pointerEvents="none"
      />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F5F5F5",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    gap: 8,
  },
  input: {
    flex: 1,
    fontSize: 14,
    color: "#1A1A2E",
    fontFamily: "Inter",
    padding: 0,
  },
});
