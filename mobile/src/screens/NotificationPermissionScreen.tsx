import React from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Notification Permission screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from notification-permission.png (375×812 @3x):
 *
 *   Allow Notification btn  x=24  y=558  w=326  h=50
 *   Maybe Later link        x=120 y=622  w=135  h=36
 *
 * No real push permission is requested — buttons navigate forward (mobile-only phase).
 * TODO: call expo-notifications requestPermissionsAsync when backend supports push.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

type Props = NativeStackScreenProps<AuthStackParamList, "NotificationPermission">;

export function NotificationPermissionScreen({ navigation }: Props) {
  const ab = artboard();

  const advance = () => navigation.navigate("PhoneEntry");

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/notification-permission.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Allow Notification */}
      <Pressable
        style={[ab.rect(24, 558, 326, 50), styles.button, DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={advance}
        accessibilityRole="button"
        accessibilityLabel="Allow Notification"
      />

      {/* Maybe Later */}
      <Pressable
        style={[ab.rect(120, 622, 135, 36), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={advance}
        hitSlop={8}
        accessibilityRole="button"
        accessibilityLabel="Maybe Later"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },
  button: { borderRadius: 999 },
  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
