import React from "react";
import { View, Text, StyleSheet, Animated } from "react-native";
import { Ionicons } from "@expo/vector-icons";

const BRAND_PRIMARY = "#4f46e5";
const COMPLETED_COLOR = "#10b981";
const INACTIVE_COLOR = "#d1d5db";

interface Step {
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}

interface StepProgressBarProps {
  steps: Step[];
  currentStep: number;
}

export default function StepProgressBar({
  steps,
  currentStep,
}: StepProgressBarProps) {
  return (
    <View style={styles.container}>
      {steps.map((step, index) => {
        const isCompleted = index < currentStep;
        const isActive = index === currentStep;
        const isLast = index === steps.length - 1;

        const circleColor = isCompleted
          ? COMPLETED_COLOR
          : isActive
          ? BRAND_PRIMARY
          : INACTIVE_COLOR;

        const lineColor = isCompleted ? COMPLETED_COLOR : "#e5e7eb";

        return (
          <View key={index} style={styles.stepWrapper}>
            <View style={styles.stepRow}>
              {/* Circle */}
              <View
                style={[
                  styles.circle,
                  {
                    backgroundColor: isCompleted || isActive ? circleColor : "#fff",
                    borderColor: circleColor,
                  },
                ]}
              >
                {isCompleted ? (
                  <Ionicons name="checkmark" size={14} color="#fff" />
                ) : (
                  <Ionicons
                    name={step.icon}
                    size={14}
                    color={isActive ? "#fff" : INACTIVE_COLOR}
                  />
                )}
              </View>

              {/* Line */}
              {!isLast && (
                <View style={styles.lineTrack}>
                  <View
                    style={[
                      styles.lineFill,
                      { backgroundColor: lineColor },
                    ]}
                  />
                </View>
              )}
            </View>

            {/* Label */}
            <Text
              style={[
                styles.label,
                {
                  color: isCompleted
                    ? COMPLETED_COLOR
                    : isActive
                    ? BRAND_PRIMARY
                    : "#9ca3af",
                  fontWeight: isActive ? "700" : "500",
                },
              ]}
              numberOfLines={1}
            >
              {step.label}
            </Text>
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: "row",
    alignItems: "flex-start",
    paddingHorizontal: 8,
    paddingVertical: 16,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  stepWrapper: {
    flex: 1,
    alignItems: "center",
  },
  stepRow: {
    flexDirection: "row",
    alignItems: "center",
    width: "100%",
    justifyContent: "center",
  },
  circle: {
    width: 28,
    height: 28,
    borderRadius: 14,
    borderWidth: 2,
    alignItems: "center",
    justifyContent: "center",
    zIndex: 1,
  },
  lineTrack: {
    flex: 1,
    height: 3,
    backgroundColor: "#f3f4f6",
    borderRadius: 1.5,
    marginLeft: -2,
    marginRight: -2,
  },
  lineFill: {
    height: "100%",
    borderRadius: 1.5,
  },
  label: {
    fontSize: 10,
    marginTop: 4,
    textAlign: "center",
  },
});
