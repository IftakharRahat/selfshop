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

// ─── Supplier brand palette (deep indigo → vivid indigo) ───
const BRAND_GRADIENT = ["#2d2a5d", "#3b3780", "#4f46e5"] as const;
const BRAND_CTA = "#4f46e5";

const SLIDES = [
  {
    id: "1",
    title: "আপনার পণ্য আপলোড করুন",
    subtitle:
      "আপনার কাছে থাকা সেরা পণ্যগুলো সেলফ-শপ প্ল্যাটফর্মে লিস্টিং করে কোটি মানুষের কাছে পৌঁছে দিন এবং আপনার ব্যবসা শুরু করুন আজই।",
    image: require("@/assets/images/onboarding/upload.png"),
  },
  {
    id: "2",
    title: "প্যাকেজিং করে রাইডারকে\nবুঝিয়ে দিন",
    subtitle:
      "অর্ডার আসার পর পণ্যটি নিরাপদে প্যাকেজিং করে আমাদের ডেলিভারি রাইডারের কাছে হস্তান্তর করুন। বাকি সব দায়িত্ব আমাদের।",
    image: require("@/assets/images/onboarding/processing.png"),
  },
  {
    id: "3",
    title: "দ্রুত ও অটোমেটিক\nপেমেন্ট বুঝে নিন",
    subtitle:
      "পণ্যটি সাকসেসফুলি ডেলিভারি হওয়ার সাথে সাথেই আপনার বিক্রিত পণ্যের টাকা স্বয়ংক্রিয়ভাবে আপনার ওয়ালেটে যোগ হয়ে যাবে।",
    image: require("@/assets/images/onboarding/payment.png"),
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
    await SecureStore.setItemAsync("supplier_onboarding_completed", "true");
    router.replace("/login");
  }

  function handleNext() {
    if (currentIndex < SLIDES.length - 1)
      flatListRef.current?.scrollToIndex({ index: currentIndex + 1 });
    else handleGetStarted();
  }

  const isLastSlide = currentIndex === SLIDES.length - 1;

  return (
    <LinearGradient
      colors={[...BRAND_GRADIENT]}
      start={{ x: 0, y: 0 }}
      end={{ x: 0.5, y: 1 }}
      style={styles.container}
    >
      {/* Skip button */}
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
            <Text fontSize={13} fontWeight="600" color="rgba(255,255,255,0.75)">
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
            {/* Image area — top half */}
            <View
              style={[styles.imageSection, { paddingTop: insets.top + 40 }]}
            >
              {/* Decorative glow ring */}
              <View style={styles.glowOuter} />
              <View style={styles.glowInner} />
              <Image
                source={item.image}
                style={styles.image}
                resizeMode="contain"
              />
            </View>

            {/* Text area — bottom half */}
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
        {/* Dots */}
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

        {/* CTA Button — white pill */}
        <Pressable
          onPress={handleNext}
          style={({ pressed }) => [
            styles.ctaButton,
            pressed && styles.ctaButtonPressed,
          ]}
        >
          <Text style={styles.ctaText}>
            {isLastSlide ? "এখনই শুরু করুন" : "পরবর্তী"}
          </Text>
        </Pressable>
      </Animated.View>
    </LinearGradient>
  );
}

// ─── Layout constants ───
const IMAGE_SIZE = width * 0.82;
const GLOW_SIZE = IMAGE_SIZE * 0.78;

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },

  /* ─ Skip ─ */
  skipWrapper: {
    position: "absolute",
    right: 20,
    zIndex: 10,
  },
  skipButton: {
    paddingVertical: 6,
    paddingHorizontal: 18,
    borderRadius: 20,
    backgroundColor: "rgba(255,255,255,0.12)",
    borderWidth: 1,
    borderColor: "rgba(255,255,255,0.18)",
  },

  /* ─ Slide ─ */
  slide: {
    width,
    flex: 1,
  },

  /* ─ Image area ─ */
  imageSection: {
    width,
    height: height * 0.48,
    justifyContent: "center",
    alignItems: "center",
  },
  glowOuter: {
    position: "absolute",
    width: GLOW_SIZE * 1.15,
    height: GLOW_SIZE * 1.15,
    borderRadius: GLOW_SIZE * 0.575,
    backgroundColor: "rgba(79,70,229,0.08)",
  },
  glowInner: {
    position: "absolute",
    width: GLOW_SIZE,
    height: GLOW_SIZE,
    borderRadius: GLOW_SIZE * 0.5,
    backgroundColor: "rgba(255,255,255,0.10)",
  },
  image: {
    width: IMAGE_SIZE,
    height: IMAGE_SIZE,
  },

  /* ─ Text area ─ */
  textSection: {
    flex: 1,
    paddingHorizontal: 30,
    paddingTop: 10,
    alignItems: "center",
    justifyContent: "flex-start",
  },
  title: {
    fontSize: 24,
    fontWeight: "800",
    color: "#FFFFFF",
    textAlign: "center",
    lineHeight: 37,
    letterSpacing: 0.2,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: "400",
    color: "rgba(255,255,255,0.65)",
    textAlign: "center",
    lineHeight: 24,
    marginTop: 16,
    paddingHorizontal: 6,
  },

  /* ─ Bottom ─ */
  bottomSection: {
    paddingHorizontal: 28,
    alignItems: "center",
    gap: 24,
  },

  /* ─ Dots ─ */
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
    backgroundColor: "#FFFFFF",
  },
  dotInactive: {
    width: 8,
    backgroundColor: "rgba(255,255,255,0.30)",
  },

  /* ─ CTA ─ */
  ctaButton: {
    width: "100%",
    borderRadius: 28,
    backgroundColor: "#FFFFFF",
    paddingVertical: 17,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.12,
    shadowRadius: 14,
    elevation: 8,
  },
  ctaButtonPressed: {
    opacity: 0.92,
    transform: [{ scale: 0.98 }],
  },
  ctaText: {
    fontSize: 17,
    fontWeight: "700",
    color: BRAND_CTA,
    letterSpacing: 0.3,
  },
});
