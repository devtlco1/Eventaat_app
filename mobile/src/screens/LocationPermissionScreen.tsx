import React from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Location Permission screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from location-permission.png (375×812 @3x):
 *
 *   Allow Location Access btn  x=24  y=558  w=326  h=50
 *   Enter Location Manually    x=88  y=622  w=200  h=36
 *
 * No real location API is called — buttons navigate forward (mobile-only phase).
 * TODO: call expo-location requestForegroundPermissionsAsync when backend supports it.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

type Props = NativeStackScreenProps<AuthStackParamList, "LocationPermission">;

export function LocationPermissionScreen({ navigation }: Props) {
  const ab = artboard();

  const advance = () => navigation.navigate("NotificationPermission");

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/location-permission.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Allow Location Access */}
      <Pressable
        style={[ab.rect(24, 558, 326, 50), styles.button, DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={advance}
        accessibilityRole="button"
        accessibilityLabel="Allow Location Access"
      />

      {/* Enter Location Manually */}
      <Pressable
        style={[ab.rect(88, 622, 200, 36), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={advance}
        hitSlop={8}
        accessibilityRole="button"
        accessibilityLabel="Enter Location Manually"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },
  button: { borderRadius: 999 },
  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
