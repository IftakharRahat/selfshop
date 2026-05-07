import { useState } from "react";
import {
  View,
  StyleSheet,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { WebView } from "react-native-webview";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useQuery } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

export default function LiveChatScreen() {
  const insets = useSafeAreaInsets();
  const [chatReady, setChatReady] = useState(false);

  const infoQuery = useQuery({
    queryKey: ["basic-info"],
    queryFn: async () => {
      const { data } = await apiClient.get("/basic-info");
      return data?.data ?? data;
    },
  });

  const chatScript = infoQuery.data?.chat_box ?? "";
  const isLoading = infoQuery.isLoading;

  const chatHtml = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; height: 100%; overflow: hidden; background: #F8F8FA; }

    #loader {
      position: fixed; inset: 0; z-index: 1;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      background: #F8F8FA; gap: 16px;
      font-family: -apple-system, BlinkMacSystemFont, sans-serif;
      color: #8E8E93; font-size: 14px;
    }
    .dots { display: flex; gap: 6px; }
    .dots span {
      width: 10px; height: 10px; border-radius: 50%; background: ${ACCENT};
      animation: pulse 1.4s infinite ease-in-out both;
    }
    .dots span:nth-child(1) { animation-delay: -0.32s; }
    .dots span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes pulse {
      0%, 80%, 100% { transform: scale(0.4); opacity: 0.4; }
      40% { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body>
  <div id="loader">
    <div class="dots"><span></span><span></span><span></span></div>
    <p>Connecting to support...</p>
  </div>

  <script type="text/javascript">
    var Tawk_API = Tawk_API || {};
    var Tawk_LoadStart = new Date();

    Tawk_API.onLoad = function() {
      Tawk_API.maximize();
    };
    Tawk_API.onChatMaximized = function() {
      document.getElementById('loader').style.display = 'none';
      window.ReactNativeWebView && window.ReactNativeWebView.postMessage('CHAT_READY');
    };
  </script>

  ${chatScript}

  <script>
    var attempts = 0;
    var iv = setInterval(function() {
      attempts++;
      try {
        if (typeof Tawk_API !== 'undefined' && typeof Tawk_API.maximize === 'function') {
          Tawk_API.maximize();
        }
      } catch(e) {}
      if (attempts > 30) {
        clearInterval(iv);
        document.getElementById('loader').style.display = 'none';
        window.ReactNativeWebView && window.ReactNativeWebView.postMessage('CHAT_READY');
      }
    }, 500);
  </script>
</body>
</html>`;

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Live Chat",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={[styles.container, { paddingBottom: insets.bottom }]}>
        {(isLoading || !chatReady) && (
          <View style={styles.overlayLoader}>
            <View style={styles.loaderIcon}>
              <Ionicons name="chatbubbles" size={36} color={ACCENT} />
            </View>
            <ActivityIndicator
              size="large"
              color={ACCENT}
              style={{ marginTop: 20 }}
            />
            <Text style={styles.statusText}>
              {isLoading ? "Loading..." : "Connecting to support..."}
            </Text>
          </View>
        )}
        {!isLoading && chatScript ? (
          <WebView
            source={{
              html: chatHtml,
              baseUrl: "https://selfshop.com.bd",
            }}
            style={[styles.webview, !chatReady && { opacity: 0 }]}
            javaScriptEnabled
            domStorageEnabled
            originWhitelist={["*"]}
            mixedContentMode="always"
            setSupportMultipleWindows={false}
            onMessage={(event) => {
              if (event.nativeEvent.data === "CHAT_READY") {
                setChatReady(true);
              }
            }}
          />
        ) : null}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F8F8FA",
  },
  overlayLoader: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F8F8FA",
    zIndex: 10,
  },
  loaderIcon: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  statusText: {
    fontSize: 14,
    color: "#8E8E93",
    marginTop: 12,
  },
  webview: {
    flex: 1,
    backgroundColor: "#F8F8FA",
  },
});
