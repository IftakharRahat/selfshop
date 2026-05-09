import { useCallback, useEffect, useMemo, useState } from "react";
import {
  View,
  StyleSheet,
  ActivityIndicator,
  Pressable,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { WebView } from "react-native-webview";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useQuery } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const CHAT_READY_FALLBACK_MS = 8000;

export default function LiveChatScreen() {
  const insets = useSafeAreaInsets();
  const [chatReady, setChatReady] = useState(false);
  const [chatError, setChatError] = useState<string | null>(null);
  const [webViewKey, setWebViewKey] = useState(0);

  const infoQuery = useQuery({
    queryKey: ["basic-info"],
    queryFn: async () => {
      const { data } = await apiClient.get("/basic-info");
      return data?.data ?? data;
    },
  });

  const chatScript = typeof infoQuery.data?.chat_box === "string"
    ? infoQuery.data.chat_box.trim()
    : "";
  const isLoading = infoQuery.isLoading;
  const hasChatScript = chatScript.length > 0;
  const showFallback = !isLoading && (infoQuery.isError || !!chatError || !hasChatScript);
  const showOverlay = isLoading || (hasChatScript && !chatReady && !chatError);

  useEffect(() => {
    setChatReady(false);
    setChatError(null);
  }, [chatScript]);

  useEffect(() => {
    if (isLoading || !hasChatScript || chatReady || chatError) return;

    const timeout = setTimeout(() => {
      setChatReady(true);
    }, CHAT_READY_FALLBACK_MS);

    return () => clearTimeout(timeout);
  }, [chatError, chatReady, hasChatScript, isLoading]);

  const markChatReady = useCallback(() => {
    setChatReady(true);
  }, []);

  const retryChat = useCallback(() => {
    setChatError(null);
    setChatReady(false);
    setWebViewKey((key) => key + 1);
    infoQuery.refetch();
  }, [infoQuery]);

  const chatHtml = useMemo(() => `
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
    var isChatReady = false;

    function postChatReady() {
      if (isChatReady) return;
      isChatReady = true;
      var loader = document.getElementById('loader');
      if (loader) loader.style.display = 'none';
      window.ReactNativeWebView && window.ReactNativeWebView.postMessage('CHAT_READY');
    }

    function tryMaximize() {
      try {
        if (typeof Tawk_API !== 'undefined' && typeof Tawk_API.maximize === 'function') {
          Tawk_API.maximize();
        }
      } catch(e) {}
    }

    Tawk_API.onLoad = function() {
      postChatReady();
      setTimeout(tryMaximize, 250);
    };
    Tawk_API.onChatMaximized = function() {
      postChatReady();
    };
  </script>

  ${chatScript}

  <script>
    var attempts = 0;
    var iv = setInterval(function() {
      attempts++;
      tryMaximize();

      if (typeof Tawk_API !== 'undefined' && typeof Tawk_API.maximize === 'function') {
        postChatReady();
      }

      if (attempts > 20) {
        clearInterval(iv);
        postChatReady();
      }
    }, 500);
  </script>
</body>
</html>`, [chatScript]);

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
        {hasChatScript && !chatError ? (
          <WebView
            key={webViewKey}
            source={{
              html: chatHtml,
              baseUrl: "https://selfshop.com.bd",
            }}
            style={styles.webview}
            javaScriptEnabled
            domStorageEnabled
            originWhitelist={["*"]}
            mixedContentMode="always"
            thirdPartyCookiesEnabled
            sharedCookiesEnabled
            setSupportMultipleWindows={false}
            onLoadEnd={markChatReady}
            onError={() => {
              setChatError("Live chat failed to load.");
              setChatReady(true);
            }}
            onHttpError={() => {
              setChatError("Live chat service is unavailable.");
              setChatReady(true);
            }}
            onMessage={(event) => {
              if (event.nativeEvent.data === "CHAT_READY") {
                markChatReady();
              }
            }}
          />
        ) : null}

        {showOverlay && (
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

        {showFallback && (
          <View style={styles.fallback}>
            <View style={styles.loaderIcon}>
              <Ionicons name="chatbubble-ellipses" size={36} color={ACCENT} />
            </View>
            <Text style={styles.fallbackTitle}>Live chat is unavailable</Text>
            <Text style={styles.fallbackText}>
              {chatError ??
                (infoQuery.isError
                  ? "Could not load live chat settings."
                  : "Support chat has not been configured yet.")}
            </Text>
            <Pressable style={styles.retryButton} onPress={retryChat}>
              <Text style={styles.retryText}>Try again</Text>
            </Pressable>
          </View>
        )}
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
    elevation: 10,
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
  fallback: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 32,
    backgroundColor: "#F8F8FA",
  },
  fallbackTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#1A1A2E",
    marginTop: 18,
  },
  fallbackText: {
    fontSize: 14,
    color: "#8E8E93",
    marginTop: 8,
    textAlign: "center",
    lineHeight: 20,
  },
  retryButton: {
    marginTop: 20,
    minHeight: 44,
    paddingHorizontal: 22,
    borderRadius: 22,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: ACCENT,
  },
  retryText: {
    color: "#FFFFFF",
    fontSize: 14,
    fontWeight: "700",
  },
});
