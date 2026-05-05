import React from "react";
import { NavigationContainer } from "@react-navigation/native";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { createBottomTabNavigator } from "@react-navigation/bottom-tabs";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "../auth/AuthContext";
import { needsName } from "../auth/profile";
import { colors } from "../theme/tokens";
import { SplashScreen } from "../screens/SplashScreen";
import { PhoneEntryScreen } from "../screens/PhoneEntryScreen";
import { SignUpScreen } from "../screens/SignUpScreen";
import { OtpVerifyScreen } from "../screens/OtpVerifyScreen";
import { CompleteProfileScreen } from "../screens/CompleteProfileScreen";
import { RestaurantListScreen } from "../screens/RestaurantListScreen";
import { RestaurantDetailsScreen } from "../screens/RestaurantDetailsScreen";
import { CreateBookingScreen } from "../screens/CreateBookingScreen";
import { MyBookingsScreen } from "../screens/MyBookingsScreen";
import { BookingDetailsScreen } from "../screens/BookingDetailsScreen";
import { ProfileScreen } from "../screens/ProfileScreen";

// ---- Param lists ----

export type AuthStackParamList = {
  PhoneEntry: undefined;
  SignUp: undefined;
  /** mode: 'login' → never show name field; 'signup' → may show name field */
  OtpVerify: { phone: string; name?: string; mode: "login" | "signup" };
};

export type ExploreStackParamList = {
  RestaurantList: undefined;
  RestaurantDetails: { slug: string };
  CreateBooking: { restaurantSlug?: string };
  BookingDetails: { bookingId: number };
};

export type BookingsStackParamList = {
  MyBookings: undefined;
  BookingDetails: { bookingId: number };
};

export type ProfileStackParamList = {
  Profile: undefined;
};

export type TabParamList = {
  Explore: undefined;
  Bookings: undefined;
  ProfileTab: undefined;
};

/** Legacy union kept for screen files that import this type. */
export type RootStackParamList = AuthStackParamList &
  ExploreStackParamList &
  BookingsStackParamList &
  ProfileStackParamList & {
    CompleteProfile: undefined;
    Home: undefined;
    Restaurants: undefined;
  };

// ---- Navigators ----

const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const ExploreStack = createNativeStackNavigator<ExploreStackParamList>();
const BookingsStack = createNativeStackNavigator<BookingsStackParamList>();
const ProfileStack = createNativeStackNavigator<ProfileStackParamList>();
const Tab = createBottomTabNavigator<TabParamList>();
const RootStack = createNativeStackNavigator();

function ExploreNavigator() {
  return (
    <ExploreStack.Navigator screenOptions={{ headerTintColor: colors.text }}>
      <ExploreStack.Screen
        name="RestaurantList"
        component={RestaurantListScreen}
        options={{ title: "Explore" }}
      />
      <ExploreStack.Screen
        name="RestaurantDetails"
        component={RestaurantDetailsScreen}
        options={{ title: "Restaurant" }}
      />
      <ExploreStack.Screen
        name="CreateBooking"
        component={CreateBookingScreen}
        options={{ title: "New booking" }}
      />
      <ExploreStack.Screen
        name="BookingDetails"
        component={BookingDetailsScreen}
        options={{ title: "Booking" }}
      />
    </ExploreStack.Navigator>
  );
}

function BookingsNavigator() {
  return (
    <BookingsStack.Navigator screenOptions={{ headerTintColor: colors.text }}>
      <BookingsStack.Screen
        name="MyBookings"
        component={MyBookingsScreen}
        options={{ title: "My bookings" }}
      />
      <BookingsStack.Screen
        name="BookingDetails"
        component={BookingDetailsScreen}
        options={{ title: "Booking" }}
      />
    </BookingsStack.Navigator>
  );
}

function ProfileNavigator() {
  return (
    <ProfileStack.Navigator screenOptions={{ headerTintColor: colors.text }}>
      <ProfileStack.Screen
        name="Profile"
        component={ProfileScreen}
        options={{ title: "Profile" }}
      />
    </ProfileStack.Navigator>
  );
}

function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          borderTopColor: colors.border,
          backgroundColor: colors.background,
        },
      }}
    >
      <Tab.Screen
        name="Explore"
        component={ExploreNavigator}
        options={{
          title: "Explore",
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="compass-outline" color={color} size={size} />
          ),
        }}
      />
      <Tab.Screen
        name="Bookings"
        component={BookingsNavigator}
        options={{
          title: "Bookings",
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="calendar-outline" color={color} size={size} />
          ),
        }}
      />
      <Tab.Screen
        name="ProfileTab"
        component={ProfileNavigator}
        options={{
          title: "Profile",
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="person-outline" color={color} size={size} />
          ),
        }}
      />
    </Tab.Navigator>
  );
}

export function AppNavigator() {
  const { token, me, isBootstrapping } = useAuth();

  if (isBootstrapping) {
    return <SplashScreen />;
  }

  const authed = !!token;
  const needProfile = authed && needsName(me);

  return (
    <NavigationContainer>
      {!authed ? (
        <AuthStack.Navigator screenOptions={{ headerShown: false }}>
          <AuthStack.Screen name="PhoneEntry" component={PhoneEntryScreen} />
          <AuthStack.Screen name="SignUp" component={SignUpScreen} />
          <AuthStack.Screen name="OtpVerify" component={OtpVerifyScreen} />
        </AuthStack.Navigator>
      ) : needProfile ? (
        <RootStack.Navigator screenOptions={{ headerShown: false }}>
          <RootStack.Screen name="CompleteProfile" component={CompleteProfileScreen} />
        </RootStack.Navigator>
      ) : (
        <MainTabs />
      )}
    </NavigationContainer>
  );
}
