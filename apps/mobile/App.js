import React, { useMemo, useState } from 'react';
import { ActivityIndicator, SafeAreaView, StyleSheet, Text, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { WebView } from 'react-native-webview';

const DEFAULT_SMARTPROBOOK_URL = 'https://smartprobook.com';

export default function App() {
  const appUrl = useMemo(
    () => process.env.EXPO_PUBLIC_SMARTPROBOOK_URL || DEFAULT_SMARTPROBOOK_URL,
    []
  );
  const [loadFailed, setLoadFailed] = useState(false);

  if (loadFailed) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar style="light" />
        <View style={styles.messagePanel}>
          <Text style={styles.title}>SmartProbook</Text>
          <Text style={styles.message}>
            We could not reach the platform. Please check your internet connection and try again.
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="light" />
      <WebView
        source={{ uri: appUrl }}
        sharedCookiesEnabled
        thirdPartyCookiesEnabled
        javaScriptEnabled
        domStorageEnabled
        startInLoadingState
        onError={() => setLoadFailed(true)}
        renderLoading={() => (
          <View style={styles.loader}>
            <ActivityIndicator size="large" color="#d7a928" />
            <Text style={styles.loaderText}>Opening SmartProbook...</Text>
          </View>
        )}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#061a44'
  },
  loader: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#061a44'
  },
  loaderText: {
    marginTop: 12,
    color: '#ffffff',
    fontWeight: '700'
  },
  messagePanel: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 28
  },
  title: {
    color: '#ffffff',
    fontSize: 28,
    fontWeight: '800',
    marginBottom: 12
  },
  message: {
    color: '#dbeafe',
    fontSize: 16,
    lineHeight: 24,
    textAlign: 'center'
  }
});
