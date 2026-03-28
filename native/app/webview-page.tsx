import { useState } from "react";
import {
  View,
  StyleSheet,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams } from "expo-router";
import { WebView } from "react-native-webview";

const ACCENT = "#E5005F";

export default function WebViewPageScreen() {
  const { url, title } = useLocalSearchParams<{ url: string; title?: string }>();
  const [loading, setLoading] = useState(true);

  const pageTitle = title || "Info";
  const pageUrl = url || "about:blank";

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: pageTitle,
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
        }}
      />
      <View style={styles.container}>
        {loading && (
          <View style={styles.loadingOverlay}>
            <ActivityIndicator size="large" color={ACCENT} />
            <Text fontSize="$3" color="#9CA3AF" mt="$3">
              Loading...
            </Text>
          </View>
        )}
        <WebView
          source={{ uri: pageUrl }}
          style={styles.webview}
          onLoadStart={() => setLoading(true)}
          onLoadEnd={() => setLoading(false)}
          onError={() => setLoading(false)}
          javaScriptEnabled
          domStorageEnabled
          startInLoadingState={false}
          showsVerticalScrollIndicator={false}
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  webview: { flex: 1 },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
    zIndex: 10,
  },
});
