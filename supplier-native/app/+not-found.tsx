import { View, Text, StyleSheet } from "react-native";
import { Link, Stack } from "expo-router";
import { BRAND } from "@/lib/constants";

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: "Not Found" }} />
      <View style={styles.container}>
        <Text style={styles.title}>Page not found</Text>
        <Link href="/" style={styles.link}>
          <Text style={{ color: BRAND.primary, fontWeight: "600" }}>Go to Dashboard</Text>
        </Link>
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, alignItems: "center", justifyContent: "center", padding: 20, backgroundColor: "#fff" },
  title: { fontSize: 20, fontWeight: "bold", color: "#1a1a2e" },
  link: { marginTop: 16, paddingVertical: 10 },
});
