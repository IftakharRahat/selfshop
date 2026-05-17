import { useCallback, useState } from "react";
import { Pressable, StyleSheet, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Dialog, Text } from "tamagui";

const ACCENT = "#E5005F";

export type AppDialogTone = "success" | "error" | "warning" | "danger" | "info";
export type AppDialogActionTone = "primary" | "danger" | "neutral";

export type AppDialogAction = {
  label: string;
  tone?: AppDialogActionTone;
  onPress?: () => void;
  closeOnPress?: boolean;
};

export type AppDialogOptions = {
  title: string;
  message: string;
  tone?: AppDialogTone;
  actions?: AppDialogAction[];
  onClose?: () => void;
};

export type AppDialogState = AppDialogOptions & {
  open: boolean;
  tone: AppDialogTone;
};

const EMPTY_DIALOG: AppDialogState = {
  open: false,
  tone: "info",
  title: "",
  message: "",
};

const TONE_META: Record<AppDialogTone, { icon: keyof typeof Ionicons.glyphMap; color: string; bg: string }> = {
  success: { icon: "checkmark-circle", color: "#059669", bg: "#ECFDF5" },
  error: { icon: "alert-circle", color: "#DC2626", bg: "#FEF2F2" },
  warning: { icon: "information-circle", color: "#D97706", bg: "#FFFBEB" },
  danger: { icon: "trash", color: "#DC2626", bg: "#FEF2F2" },
  info: { icon: "information-circle", color: ACCENT, bg: "#FDF2F8" },
};

export function useAppDialog() {
  const [dialog, setDialog] = useState<AppDialogState>(EMPTY_DIALOG);

  const showDialog = useCallback((options: AppDialogOptions) => {
    setDialog({
      ...options,
      open: true,
      tone: options.tone ?? "info",
    });
  }, []);

  const closeDialog = useCallback(() => {
    setDialog((current) => ({ ...current, open: false }));
  }, []);

  return { dialog, showDialog, closeDialog };
}

export function AppDialog({
  state,
  onClose,
}: {
  state: AppDialogState;
  onClose: () => void;
}) {
  const meta = TONE_META[state.tone];
  const actions = state.actions?.length
    ? state.actions
    : [{ label: "Got it", tone: "primary" as const }];

  const close = () => {
    state.onClose?.();
    onClose();
  };

  const runAction = (action: AppDialogAction) => {
    if (action.closeOnPress !== false) {
      close();
    }
    action.onPress?.();
  };

  return (
    <Dialog
      modal
      open={state.open}
      onOpenChange={(open) => {
        if (!open) close();
      }}
    >
      <Dialog.Portal>
        <Dialog.Overlay style={styles.overlay} />
        <View style={styles.centerer} pointerEvents="box-none">
          <Dialog.Content style={styles.content}>
            <View style={styles.header}>
              <View style={[styles.icon, { backgroundColor: meta.bg }]}>
                <Ionicons name={meta.icon} size={24} color={meta.color} />
              </View>
              <Pressable style={styles.closeButton} onPress={close}>
                <Ionicons name="close" size={20} color="#6B7280" />
              </Pressable>
            </View>

            <Dialog.Title asChild>
              <Text style={styles.title}>{state.title}</Text>
            </Dialog.Title>
            <Dialog.Description asChild>
              <Text style={styles.message}>{state.message}</Text>
            </Dialog.Description>

            <View style={[styles.actions, actions.length === 1 && styles.singleAction]}>
              {actions.map((action) => {
                const tone = action.tone ?? "primary";
                return (
                  <Pressable
                    key={action.label}
                    style={({ pressed }) => [
                      styles.actionButton,
                      tone === "primary" && styles.primaryButton,
                      tone === "danger" && styles.dangerButton,
                      tone === "neutral" && styles.neutralButton,
                      pressed && { opacity: 0.88 },
                    ]}
                    onPress={() => runAction(action)}
                  >
                    <Text
                      style={[
                        styles.actionText,
                        tone === "neutral" && styles.neutralText,
                      ]}
                    >
                      {action.label}
                    </Text>
                  </Pressable>
                );
              })}
            </View>
          </Dialog.Content>
        </View>
      </Dialog.Portal>
    </Dialog>
  );
}

const styles = StyleSheet.create({
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(17, 24, 39, 0.46)",
  },
  centerer: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 22,
  },
  content: {
    width: "100%",
    maxWidth: 380,
    backgroundColor: "#fff",
    borderRadius: 24,
    padding: 20,
    shadowColor: "#111827",
    shadowOffset: { width: 0, height: 18 },
    shadowOpacity: 0.2,
    shadowRadius: 28,
    elevation: 12,
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 14,
  },
  icon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: "center",
    justifyContent: "center",
  },
  closeButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#F3F4F6",
  },
  title: {
    fontSize: 20,
    fontWeight: "800",
    color: "#1A1A2E",
    marginBottom: 8,
  },
  message: {
    fontSize: 14,
    lineHeight: 20,
    color: "#6B7280",
  },
  actions: {
    flexDirection: "row",
    gap: 10,
    marginTop: 20,
  },
  singleAction: {
    justifyContent: "flex-end",
  },
  actionButton: {
    flex: 1,
    minHeight: 48,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 14,
  },
  primaryButton: {
    backgroundColor: ACCENT,
  },
  dangerButton: {
    backgroundColor: "#DC2626",
  },
  neutralButton: {
    backgroundColor: "#F3F4F6",
  },
  actionText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#fff",
  },
  neutralText: {
    color: "#374151",
  },
});
