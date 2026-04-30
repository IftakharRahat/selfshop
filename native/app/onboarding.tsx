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

const SLIDES = [
  {
    id: "1",
    title: "কোনো ইনভেস্টমেন্ট ছাড়াই\nশুরু করুন নিজের বিজনেস!",
    subtitle:
      "নিজের ই-কমার্স শুরু করতে এখন আর পুঁজি বা স্টকের চিন্তা নেই।\nসেলফ-শপ প্ল্যাটফর্মে কয়েক ক্লিকই যথেষ্ট।",
    image: require("@/assets/images/onboarding/business.png"),
  },
  {
    id: "2",
    title: "আপনার হয়ে প্যাকিং এবং\nডেলিভারি করবে সেলফ-শপ টিম।",
    subtitle:
      "আপনি শুধু অর্ডার নিয়ে আসবেন, আর সারাদেশে আপনার কাস্টমারের কাছে\nপণ্য পৌঁছে দেওয়ার সব দায়িত্ব আমাদের।",
    image: require("@/assets/images/onboarding/logistics.png"),
  },
  {
    id: "3",
    title: "ঘরে বসেই বুঝে নিন\nআপনার কাঙ্ক্ষিত প্রফিট।",
    subtitle:
      "পণ্যের দাম নির্ধারণ করুন আপনার ইচ্ছেমতো এবং প্রতি বিক্রয়\nশেষে প্রফিট বুঝে নিন সরাসরি আপনার ওয়ালেটে।",
    image: require("@/assets/images/onboarding/profit.png"),
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
    <View style={[styles.container, { paddingTop: insets.top }]}>
      {/* Skip button at top-right */}
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
            <Text
              fontSize={14}
              fontWeight="600"
              color="#999"
              letterSpacing={0.3}
            >
              এড়িয়ে যান
            </Text>
          </Pressable>
        </Animated.View>
      )}

      {/* Slides */}
      <FlatList
        ref={flatListRef}
        data={SLIDES}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        bounces={false}
        onViewableItemsChanged={onViewableItemsChanged}
        viewabilityConfig={viewabilityConfig}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <View style={styles.slide}>
            {/* Image area — takes the upper portion */}
            <View style={styles.imageSection}>
              <Image
                source={item.image}
                style={styles.image}
                resizeMode="contain"
              />
            </View>

            {/* Text area — lower portion */}
            <View style={styles.textSection}>
              <Animated.View entering={FadeInUp.duration(500).delay(100)}>
                <Text style={styles.title}>{item.title}</Text>
              </Animated.View>
              <Animated.View entering={FadeInUp.duration(500).delay(250)}>
                <Text style={styles.subtitle}>{item.subtitle}</Text>
              </Animated.View>
            </View>
          </View>
        )}
      />

      {/* Bottom controls */}
      <Animated.View
        entering={FadeInDown.duration(500).delay(300)}
        style={[styles.bottomSection, { paddingBottom: insets.bottom + 24 }]}
      >
        {/* Dot indicators */}
        <View style={styles.dotsContainer}>
          {SLIDES.map((_, index) => (
            <Animated.View
              key={index}
              style={[
                styles.dot,
                currentIndex === index ? styles.dotActive : styles.dotInactive,
              ]}
            />
          ))}
        </View>

        {/* CTA Button */}
        <Pressable
          onPress={handleNext}
          style={({ pressed }) => [
            styles.ctaButton,
            pressed && styles.ctaButtonPressed,
          ]}
        >
          <LinearGradient
            colors={["#F7A826", "#F08C1F"]}
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

const IMAGE_HEIGHT = height * 0.42;

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  /* Skip button */
  skipWrapper: {
    position: "absolute",
    right: 24,
    zIndex: 10,
  },
  skipButton: {
    paddingVertical: 6,
    paddingHorizontal: 14,
    borderRadius: 20,
    backgroundColor: "rgba(0,0,0,0.04)",
  },

  /* Slide layout */
  slide: {
    width,
    flex: 1,
    justifyContent: "flex-start",
    alignItems: "center",
  },
  imageSection: {
    width: width,
    height: IMAGE_HEIGHT,
    justifyContent: "center",
    alignItems: "center",
    paddingTop: 40,
  },
  image: {
    width: width * 0.72,
    height: IMAGE_HEIGHT * 0.92,
  },

  /* Text */
  textSection: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: 28,
    alignItems: "center",
    justifyContent: "flex-start",
  },
  title: {
    fontSize: 22,
    fontWeight: "800",
    color: "#1A1A2E",
    textAlign: "center",
    lineHeight: 34,
    letterSpacing: 0.2,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: "400",
    color: "#777777",
    textAlign: "center",
    lineHeight: 23,
    marginTop: 14,
    letterSpacing: 0.1,
  },

  /* Bottom */
  bottomSection: {
    paddingHorizontal: 28,
    alignItems: "center",
    gap: 24,
  },

  /* Dots */
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
    backgroundColor: "#F5A623",
  },
  dotInactive: {
    width: 8,
    backgroundColor: "#E0E0E0",
  },

  /* CTA */
  ctaButton: {
    width: "100%",
    borderRadius: 28,
    overflow: "hidden",
    // shadow
    shadowColor: "#F5A623",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.35,
    shadowRadius: 14,
    elevation: 8,
  },
  ctaButtonPressed: {
    opacity: 0.88,
    transform: [{ scale: 0.985 }],
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
    letterSpacing: 0.4,
  },
});
