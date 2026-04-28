import React from "react";
import {
  View,
  Text,
  TextInput,
  StyleSheet,
  type TextInputProps,
  type ViewStyle,
} from "react-native";

interface FormFieldProps extends Omit<TextInputProps, "style"> {
  label: string;
  required?: boolean;
  error?: string;
  helperText?: string;
  prefix?: string;
  containerStyle?: ViewStyle;
  multiline?: boolean;
}

export default function FormField({
  label,
  required,
  error,
  helperText,
  prefix,
  containerStyle,
  multiline,
  ...inputProps
}: FormFieldProps) {
  return (
    <View style={[styles.container, containerStyle]}>
      <Text style={styles.label}>
        {label}
        {required && <Text style={styles.required}> *</Text>}
      </Text>

      <View
        style={[
          styles.inputWrapper,
          multiline && styles.inputWrapperMultiline,
          error ? styles.inputWrapperError : null,
          inputProps.editable === false && styles.inputWrapperDisabled,
        ]}
      >
        {prefix && <Text style={styles.prefix}>{prefix}</Text>}
        <TextInput
          style={[
            styles.input,
            multiline && styles.inputMultiline,
            prefix ? styles.inputWithPrefix : null,
          ]}
          placeholderTextColor="#9ca3af"
          multiline={multiline}
          textAlignVertical={multiline ? "top" : "center"}
          {...inputProps}
        />
      </View>

      {error ? (
        <Text style={styles.errorText}>{error}</Text>
      ) : helperText ? (
        <Text style={styles.helperText}>{helperText}</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: 16,
  },
  label: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 6,
    marginLeft: 2,
  },
  required: {
    color: "#EF4444",
    fontWeight: "400",
  },
  inputWrapper: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f9fafb",
    borderWidth: 1.5,
    borderColor: "#e5e7eb",
    borderRadius: 12,
    overflow: "hidden",
  },
  inputWrapperMultiline: {
    alignItems: "flex-start",
    minHeight: 100,
  },
  inputWrapperError: {
    borderColor: "#EF4444",
    backgroundColor: "#FEF2F2",
  },
  inputWrapperDisabled: {
    backgroundColor: "#f3f4f6",
    opacity: 0.7,
  },
  prefix: {
    paddingLeft: 14,
    fontSize: 15,
    fontWeight: "600",
    color: "#6b7280",
  },
  input: {
    flex: 1,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: "#1a1a2e",
  },
  inputWithPrefix: {
    paddingLeft: 6,
  },
  inputMultiline: {
    paddingTop: 12,
    minHeight: 100,
  },
  errorText: {
    fontSize: 11,
    color: "#EF4444",
    marginTop: 4,
    marginLeft: 2,
  },
  helperText: {
    fontSize: 11,
    color: "#9ca3af",
    marginTop: 4,
    marginLeft: 2,
  },
});
