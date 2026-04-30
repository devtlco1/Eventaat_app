import React from "react";
import { NavigationContainer } from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { useAuth } from "../auth/AuthContext";
import { needsName } from "../auth/profile";
import { PhoneEntryScreen } from "../screens/PhoneEntryScreen";
import { OtpVerifyScreen } from "../screens/OtpVerifyScreen";
import { CompleteProfileScreen } from "../screens/CompleteProfileScreen";
import { HomeScreen } from "../screens/HomeScreen";

export type RootStackParamList = {
  PhoneEntry: undefined;
  OtpVerify: { phone: string };
  CompleteProfile: undefined;
  Home: undefined;
};

const Stack = createNativeStackNavigator<RootStackParamList>();

export function AppNavigator() {
  const { token, me, isBootstrapping } = useAuth();

  if (isBootstrapping) {
    return null;
  }

  const authed = !!token;
  const needProfile = authed && needsName(me);

  return (
    <NavigationContainer>
      <Stack.Navigator>
        {!authed ? (
          <>
            <Stack.Screen
              name="PhoneEntry"
              component={PhoneEntryScreen}
              options={{ title: "Sign in" }}
            />
            <Stack.Screen
              name="OtpVerify"
              component={OtpVerifyScreen}
              options={{ title: "Verify OTP" }}
            />
          </>
        ) : needProfile ? (
          <Stack.Screen
            name="CompleteProfile"
            component={CompleteProfileScreen}
            options={{ title: "Complete profile" }}
          />
        ) : (
          <Stack.Screen
            name="Home"
            component={HomeScreen}
            options={{ title: "Eventaat" }}
          />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

