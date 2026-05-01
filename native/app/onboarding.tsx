import { useRef, useState, useCallback } from "react";
import {
  FlatList,
  Image,
  Dimensions,
  StyleSheet,
  View,
  Pressable,
  type ViewToken,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import * as SecureStore from "expo-secure-store";
import { LinearGradient } from "expo-linear-gradient";
import Animated, {
  FadeIn,
  FadeInDown,
  FadeInUp,
} from "react-native-reanimated";
import { useSafeAreaInsets } from "react-native-safe-area-context";

const { width, height } = Dimensions.get("window");

const CARD_HORIZONTAL_PADDING = 20;
const CARD_WIDTH = width - CARD_HORIZONTAL_PADDING * 2;
const CARD_HEIGHT = height * 0.52;

const SLIDES = [
  {
    id: "1",
    title: "কোনো ইনভেস্টমেন্ট ছাড়াই\nশুরু করুন নিজের বিজনেস!",
    subtitle:
      "নিজের ই-কমার্স শুরু করতে এখন আর পুঁজি বা স্টকের চিন্তা নেই। সেলফ-শপ প্ল্যাটফর্মে কয়েক ক্লিকই যথেষ্ট।",
    image: require("@/assets/images/onboarding/business.png"),
    cardBg: ["#FDF2F8", "#FCEEF5", "#FBE8F0"] as const,
  },
  {
    id: "2",
    title: "আপনার হয়ে প্যাকিং এবং\nডেলিভারি করবে সেলফ-শপ টিম।",
    subtitle:
      "আপনি শুধু অর্ডার নিয়ে আসবেন, আর সারাদেশে আপনার কাস্টমারের কাছে পণ্য পৌঁছে দেওয়ার সব দায়িত্ব আমাদের।",
    image: require("@/assets/images/onboarding/logistics.png"),
    cardBg: ["#FFF0F6", "#FEE8F0", "#FDDFE8"] as const,
  },
  {
    id: "3",
    title: "ঘরে বসেই বুঝে নিন\nআপনার কাঙ্ক্ষিত প্রফিট।",
    subtitle:
      "পণ্যের দাম নির্ধারণ করুন আপনার ইচ্ছেমতো এবং প্রতি বিক্রয় শেষে প্রফিট বুঝে নিন সরাসরি আপনার ওয়ালেটে।",
    image: require("@/assets/images/onboarding/profit.png"),
    cardBg: ["#FFF5F9", "#FEECF3", "#FDE3EC"] as const,
  },
];

export default function Onboarding() {
  const [currentIndex, setCurrentIndex] = useState(0);
  const flatListRef = useRef<FlatList>(null);
  const insets = useSafeAreaInsets();

  const onViewableItemsChanged = useCallback(
    ({ viewableItems }: { viewableItems: ViewToken[] }) => {
      if (viewableItems.length > 0 && viewableItems[0].index !== null)
        setCurrentIndex(viewableItems[0].index);
    },
    []
  );
  const viewabilityConfig = useRef({
    viewAreaCoveragePercentThreshold: 50,
  }).current;

  async function handleGetStarted() {
    await SecureStore.setItemAsync("onboarding_completed", "true");
    router.replace("/");
  }

  function handleNext() {
    if (currentIndex < SLIDES.length - 1)
      flatListRef.current?.scrollToIndex({ index: currentIndex + 1 });
    else handleGetStarted();
  }

  const isLastSlide = currentIndex === SLIDES.length - 1;

  return (
    <View style={styles.container}>
      {!isLastSlide && (
        <Animated.View
          entering={FadeIn.duration(400)}
          style={[styles.skipWrapper, { top: insets.top + 12 }]}
        >
          <Pressable
            onPress={handleGetStarted}
            style={styles.skipButton}
            hitSlop={12}
          >
            <Text fontSize={14} fontWeight="600" color="#999">
              এড়িয়ে যান
            </Text>
          </Pressable>
        </Animated.View>
      )}

      <FlatList
        ref={flatListRef}
        data={SLIDES}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        bounces={false}
        decelerationRate="fast"
        onViewableItemsChanged={onViewableItemsChanged}
        viewabilityConfig={viewabilityConfig}
        keyExtractor={(item) => item.id}
        getItemLayout={(_, index) => ({
          length: width,
          offset: width * index,
          index,
        })}
        renderItem={({ item }) => (
          <View style={styles.slide}>
            <View style={[styles.cardOuter, { marginTop: insets.top + 44 }]}>
              <LinearGradient
                colors={[...item.cardBg]}
                start={{ x: 0.5, y: 0 }}
                end={{ x: 0.5, y: 1 }}
                style={styles.imageCard}
              >
                <Image
                  source={item.image}
                  style={styles.image}
                  resizeMode="contain"
                />
              </LinearGradient>
            </View>

            <View style={styles.textSection}>
              <Animated.View
                entering={FadeInUp.duration(450).delay(80)}
                style={styles.textBlock}
              >
                <Text style={styles.title}>{item.title}</Text>
              </Animated.View>
              <Animated.View
                entering={FadeInUp.duration(450).delay(200)}
                style={styles.textBlock}
              >
                <Text style={styles.subtitle}>{item.subtitle}</Text>
              </Animated.View>
            </View>
          </View>
        )}
      />

      <Animated.View
        entering={FadeInDown.duration(500).delay(300)}
        style={[styles.bottomSection, { paddingBottom: insets.bottom + 20 }]}
      >
        <View style={styles.dotsContainer}>
          {SLIDES.map((_, index) => (
            <View
              key={index}
              style={[
                styles.dot,
                currentIndex === index ? styles.dotActive : styles.dotInactive,
              ]}
            />
          ))}
        </View>

        <Pressable
          onPress={handleNext}
          style={({ pressed }) => [
            styles.ctaButton,
            pressed && styles.ctaButtonPressed,
          ]}
        >
          <LinearGradient
            colors={["#E5005F", "#C80050"]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.ctaGradient}
          >
            <Text style={styles.ctaText}>
              {isLastSlide ? "এখনই শুরু করুন" : "পরবর্তী"}
            </Text>
          </LinearGradient>
        </Pressable>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  skipWrapper: {
    position: "absolute",
    right: 24,
    zIndex: 10,
  },
  skipButton: {
    paddingVertical: 6,
    paddingHorizontal: 4,
  },

  slide: {
    width,
    flex: 1,
  },

  cardOuter: {
    paddingHorizontal: CARD_HORIZONTAL_PADDING,
  },
  imageCard: {
    width: CARD_WIDTH,
    height: CARD_HEIGHT,
    borderRadius: 32,
    justifyContent: "center",
    alignItems: "center",
    overflow: "hidden",
  },
  image: {
    width: CARD_WIDTH * 0.78,
    height: CARD_HEIGHT * 0.78,
  },

  textSection: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: 28,
    justifyContent: "flex-start",
  },
  textBlock: {
    alignSelf: "stretch",
  },
  title: {
    fontSize: 22,
    fontWeight: "800",
    color: "#1F1F1F",
    textAlign: "left",
    lineHeight: 34,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: "400",
    color: "#888888",
    textAlign: "left",
    lineHeight: 22,
    marginTop: 12,
  },

  bottomSection: {
    paddingHorizontal: 28,
    alignItems: "center",
    gap: 22,
  },

  dotsContainer: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 10,
  },
  dot: {
    height: 8,
    borderRadius: 4,
  },
  dotActive: {
    width: 32,
    backgroundColor: "#E5005F",
  },
  dotInactive: {
    width: 8,
    backgroundColor: "#E0E0E0",
  },

  ctaButton: {
    width: "100%",
    borderRadius: 28,
    overflow: "hidden",
    shadowColor: "#E5005F",
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4,
    shadowRadius: 16,
    elevation: 10,
  },
  ctaButtonPressed: {
    opacity: 0.9,
    transform: [{ scale: 0.98 }],
  },
  ctaGradient: {
    paddingVertical: 17,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 28,
  },
  ctaText: {
    fontSize: 17,
    fontWeight: "700",
    color: "#FFFFFF",
    letterSpacing: 0.3,
  },
});
