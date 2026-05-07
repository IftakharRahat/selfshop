import { useState } from "react";
import {
  View,
  ScrollView,
  TextInput,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Alert,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useMutation, useQueryClient } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

const DEPARTMENTS = ["Billing", "Parcel Support", "Technical Support"] as const;
const PRIORITIES = ["Low", "Medium", "High"] as const;

type Department = (typeof DEPARTMENTS)[number];
type Priority = (typeof PRIORITIES)[number];
type CreateTicketPayload = {
  subject: string;
  department: Department;
  priority: Priority;
  message: string;
};

export default function CreateTicketScreen() {
  const queryClient = useQueryClient();
  const [subject, setSubject] = useState("");
  const [message, setMessage] = useState("");
  const [department, setDepartment] = useState<Department>("Billing");
  const [priority, setPriority] = useState<Priority>("Medium");

  const createMutation = useMutation({
    mutationFn: async (payload: CreateTicketPayload) => {
      const formData = new FormData();
      formData.append("subject", payload.subject);
      formData.append("department", payload.department);
      formData.append("priority", payload.priority);
      formData.append("message", payload.message);

      const { data } = await apiClient.post("/create-supportticket", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      if (data?.status === false) {
        const error: any = new Error(data?.message ?? "Failed to create ticket");
        error.response = { data };
        throw error;
      }

      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["tickets"] });
      Alert.alert("Success", "Support ticket created!", [
        { text: "OK", onPress: () => router.back() },
      ]);
    },
    onError: (err: any) => {
      const errors = err?.response?.data?.errors as Record<string, string[]> | undefined;
      const firstValidationError = errors ? Object.values(errors).flat().find(Boolean) : undefined;
      Alert.alert("Error", firstValidationError || err?.response?.data?.message || "Failed to create ticket");
    },
  });

  const isFormValid = subject.trim().length >= 3 && message.trim().length >= 10;
  const handleSubmit = () => {
    const trimmedSubject = subject.trim();
    const trimmedMessage = message.trim();

    if (trimmedSubject.length < 3) {
      Alert.alert("Invalid", "Subject must be at least 3 characters.");
      return;
    }

    if (trimmedMessage.length < 10) {
      Alert.alert("Invalid", "Message must be at least 10 characters.");
      return;
    }

    createMutation.mutate({
      subject: trimmedSubject,
      department,
      priority,
      message: trimmedMessage,
    });
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "New Ticket",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.form}>
          {/* Department */}
          <View>
            <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$2">
              Department
            </Text>
            <View style={styles.departmentList}>
              {DEPARTMENTS.map((item) => (
                <Pressable
                  key={item}
                  style={[
                    styles.departmentChip,
                    department === item && styles.departmentChipActive,
                  ]}
                  onPress={() => setDepartment(item)}
                >
                  <Text
                    fontSize="$3"
                    fontWeight={department === item ? "bold" : "500"}
                    color={department === item ? "#fff" : "#1A1A2E"}
                  >
                    {item}
                  </Text>
                </Pressable>
              ))}
            </View>
          </View>

          {/* Priority */}
          <View>
            <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$2">
              Priority
            </Text>
            <View style={styles.priorityRow}>
              {PRIORITIES.map((p) => (
                <Pressable
                  key={p}
                  style={[
                    styles.priorityChip,
                    priority === p && styles.priorityChipActive,
                    priority === p && p === "High" && { backgroundColor: "#DC2626", borderColor: "#DC2626" },
                    priority === p && p === "Low" && { backgroundColor: "#8E8E93", borderColor: "#8E8E93" },
                  ]}
                  onPress={() => setPriority(p)}
                >
                  <Text
                    fontSize="$3"
                    fontWeight={priority === p ? "bold" : "400"}
                    color={priority === p ? "#fff" : "#1A1A2E"}
                    style={{ textTransform: "capitalize" }}
                  >
                    {p}
                  </Text>
                </Pressable>
              ))}
            </View>
          </View>

          {/* Subject */}
          <View>
            <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
              Subject
            </Text>
            <TextInput
              style={styles.input}
              value={subject}
              onChangeText={setSubject}
              placeholder="Brief description of your issue"
              placeholderTextColor="#C7C7CC"
              maxLength={200}
            />
          </View>

          {/* Message */}
          <View>
            <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
              Message
            </Text>
            <TextInput
              style={[styles.input, styles.inputMultiline]}
              value={message}
              onChangeText={setMessage}
              placeholder="Describe your issue in detail..."
              placeholderTextColor="#C7C7CC"
              multiline
              numberOfLines={6}
            />
          </View>

          {/* Submit */}
          <Pressable
            style={({ pressed }) => [
              styles.submitButton,
              !isFormValid && styles.submitButtonDisabled,
              pressed && isFormValid && { opacity: 0.85 },
            ]}
            onPress={handleSubmit}
            disabled={createMutation.isPending}
          >
            {createMutation.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                Submit Ticket
              </Text>
            )}
          </Pressable>
        </View>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  form: { padding: 20, gap: 20 },
  departmentList: {
    gap: 10,
  },
  departmentChip: {
    paddingVertical: 12,
    paddingHorizontal: 14,
    borderRadius: 12,
    backgroundColor: "#F5F5F5",
    borderWidth: 1,
    borderColor: "#F0F0F0",
    alignItems: "center",
  },
  departmentChipActive: {
    backgroundColor: "#E5005F",
    borderColor: "#E5005F",
  },
  priorityRow: {
    flexDirection: "row",
    gap: 10,
  },
  priorityChip: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: "#F5F5F5",
    borderWidth: 1,
    borderColor: "#F0F0F0",
    alignItems: "center",
  },
  priorityChipActive: {
    backgroundColor: "#E5005F",
    borderColor: "#E5005F",
  },
  input: {
    borderWidth: 1,
    borderColor: "#E8E8E8",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: "#1A1A2E",
    backgroundColor: "#FAFAFA",
  },
  inputMultiline: {
    minHeight: 140,
    textAlignVertical: "top",
  },
  submitButton: {
    backgroundColor: "#E5005F",
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: "center",
  },
  submitButtonDisabled: {
    opacity: 0.5,
  },
});
