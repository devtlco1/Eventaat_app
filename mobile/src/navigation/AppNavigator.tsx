import React from "react";
import { NavigationContainer } from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { useAuth } from "../auth/AuthContext";
import { needsName } from "../auth/profile";
import { SplashScreen } from "../screens/SplashScreen";
import { PhoneEntryScreen } from "../screens/PhoneEntryScreen";
import { SignUpScreen } from "../screens/SignUpScreen";
import { OtpVerifyScreen } from "../screens/OtpVerifyScreen";
import { CompleteProfileScreen } from "../screens/CompleteProfileScreen";
import { HomeScreen } from "../screens/HomeScreen";
import { RestaurantListScreen } from "../screens/RestaurantListScreen";
import { RestaurantDetailsScreen } from "../screens/RestaurantDetailsScreen";
import { CreateBookingScreen } from "../screens/CreateBookingScreen";
import { MyBookingsScreen } from "../screens/MyBookingsScreen";
import { BookingDetailsScreen } from "../screens/BookingDetailsScreen";
import { ProfileScreen } from "../screens/ProfileScreen";

export type RootStackParamList = {
  PhoneEntry: undefined;
  SignUp: undefined;
  OtpVerify: { phone: string; name?: string };
  CompleteProfile: undefined;
  Home: undefined;
  Restaurants: undefined;
  RestaurantDetails: { slug: string };
  CreateBooking: { restaurantSlug?: string };
  MyBookings: undefined;
  BookingDetails: { bookingId: number };
  Profile: undefined;
};

const Stack = createNativeStackNavigator<RootStackParamList>();

export function AppNavigator() {
  const { token, me, isBootstrapping } = useAuth();

  if (isBootstrapping) {
    return <SplashScreen />;
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
              options={{ headerShown: false }}
            />
            <Stack.Screen
              name="SignUp"
              component={SignUpScreen}
              options={{ headerShown: false }}
            />
            <Stack.Screen
              name="OtpVerify"
              component={OtpVerifyScreen}
              options={{ headerShown: false }}
            />
          </>
        ) : needProfile ? (
          <Stack.Screen
            name="CompleteProfile"
            component={CompleteProfileScreen}
            options={{ headerShown: false }}
          />
        ) : (
          <>
            <Stack.Screen
              name="Home"
              component={HomeScreen}
              options={{ headerShown: false }}
            />
            <Stack.Screen
              name="Restaurants"
              component={RestaurantListScreen}
              options={{ title: "Restaurants" }}
            />
            <Stack.Screen
              name="RestaurantDetails"
              component={RestaurantDetailsScreen}
              options={{ title: "Restaurant details" }}
            />
            <Stack.Screen
              name="CreateBooking"
              component={CreateBookingScreen}
              options={{ title: "Create booking" }}
            />
            <Stack.Screen
              name="MyBookings"
              component={MyBookingsScreen}
              options={{ title: "My bookings" }}
            />
            <Stack.Screen
              name="BookingDetails"
              component={BookingDetailsScreen}
              options={{ title: "Booking" }}
            />
            <Stack.Screen
              name="Profile"
              component={ProfileScreen}
              options={{ title: "Profile" }}
            />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
