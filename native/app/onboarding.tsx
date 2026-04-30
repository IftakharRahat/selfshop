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
    // warm cream to match the image's off-white bg
    bgColors: ["#FFF8EE", "#FFF1DD", "#FFECCE"] as const,
  },
  {
    id: "2",
    title: "আপনার হয়ে প্যাকিং এবং\nডেলিভারি করবে সেলফ-শপ টিম।",
    subtitle:
      "আপনি শুধু অর্ডার নিয়ে আসবেন, আর সারাদেশে আপনার কাস্টমারের কাছে\nপণ্য পৌঁছে দেওয়ার সব দায়িত্ব আমাদের।",
    image: require("@/assets/images/onboarding/logistics.png"),
    bgColors: ["#FFF6EA", "#FFEED6", "#FFE6C2"] as const,
  },
  {
    id: "3",
    title: "ঘরে বসেই বুঝে নিন\nআপনার কাঙ্ক্ষিত প্রফিট।",
    subtitle:
      "পণ্যের দাম নির্ধারণ করুন আপনার ইচ্ছেমতো এবং প্রতি বিক্রয়\nশেষে প্রফিট বুঝে নিন সরাসরি আপনার ওয়ালেটে।",
    image: require("@/assets/images/onboarding/profit.png"),
    bgColors: ["#FFF9F0", "#FFF0DC", "#FFE8C8"] as const,
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
      {/* Skip button at top-right */}
      {!isLastSlide && (
        <Animated.View
          entering={FadeIn.duration(400)}
          style={[styles.skipWrapper, { top: insets.top + 10 }]}
        >
          <Pressable
            onPress={handleGetStarted}
            style={styles.skipButton}
            hitSlop={12}
          >
            <Text fontSize={13} fontWeight="600" color="#B0855A">
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
            {/* Image area — warm gradient background to blend with image bg */}
            <LinearGradient
              colors={[...item.bgColors]}
              start={{ x: 0.5, y: 0 }}
              end={{ x: 0.5, y: 1 }}
              style={[styles.imageSection, { paddingTop: insets.top + 20 }]}
            >
              <Image
                source={item.image}
                style={styles.image}
                resizeMode="contain"
              />
            </LinearGradient>

            {/* Curved transition from gradient to white */}
            <View style={styles.curveOverlay} />

            {/* Text area */}
            <View style={styles.textSection}>
              <Animated.View entering={FadeInUp.duration(450).delay(80)}>
                <Text style={styles.title}>{item.title}</Text>
              </Animated.View>
              <Animated.View entering={FadeInUp.duration(450).delay(200)}>
                <Text style={styles.subtitle}>{item.subtitle}</Text>
              </Animated.View>
            </View>
          </View>
        )}
      />

      {/* Bottom controls */}
      <Animated.View
        entering={FadeInDown.duration(500).delay(300)}
        style={[styles.bottomSection, { paddingBottom: insets.bottom + 20 }]}
      >
        {/* Dot indicators */}
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

        {/* CTA Button */}
        <Pressable
          onPress={handleNext}
          style={({ pressed }) => [
            styles.ctaButton,
            pressed && styles.ctaButtonPressed,
          ]}
        >
          <LinearGradient
            colors={["#FABA4A", "#F09819"]}
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

const IMAGE_SECTION_HEIGHT = height * 0.50;

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  /* Skip */
  skipWrapper: {
    position: "absolute",
    right: 20,
    zIndex: 10,
  },
  skipButton: {
    paddingVertical: 5,
    paddingHorizontal: 16,
    borderRadius: 20,
    backgroundColor: "rgba(255,255,255,0.65)",
  },

  /* Slide */
  slide: {
    width,
    flex: 1,
  },

  /* Image section — full-width warm gradient */
  imageSection: {
    width: width,
    height: IMAGE_SECTION_HEIGHT,
    justifyContent: "center",
    alignItems: "center",
    overflow: "hidden",
  },
  image: {
    width: width * 0.82,
    height: IMAGE_SECTION_HEIGHT * 0.88,
  },

  /* Smooth curved transition between gradient and white text area */
  curveOverlay: {
    width: width,
    height: 40,
    marginTop: -40,
    backgroundColor: "#FFFFFF",
    borderTopLeftRadius: 32,
    borderTopRightRadius: 32,
  },

  /* Text */
  textSection: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: 6,
    alignItems: "center",
    justifyContent: "flex-start",
  },
  title: {
    fontSize: 22,
    fontWeight: "800",
    color: "#1F1F1F",
    textAlign: "center",
    lineHeight: 34,
  },
  subtitle: {
    fontSize: 14,
    fontWeight: "400",
    color: "#888888",
    textAlign: "center",
    lineHeight: 23,
    marginTop: 12,
  },

  /* Bottom */
  bottomSection: {
    paddingHorizontal: 28,
    alignItems: "center",
    gap: 22,
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
    shadowColor: "#F09819",
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
