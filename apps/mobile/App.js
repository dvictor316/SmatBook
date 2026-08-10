import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  BackHandler,
  Pressable,
  SafeAreaView,
  StyleSheet,
  Text,
  View
} from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { WebView } from 'react-native-webview';

const DEFAULT_SMARTPROBOOK_URL = 'https://smartprobook.com';

export default function App() {
  const appUrl = useMemo(
    () => process.env.EXPO_PUBLIC_SMARTPROBOOK_URL || DEFAULT_SMARTPROBOOK_URL,
    []
  );
  const webViewRef = useRef(null);
  const [loadFailed, setLoadFailed] = useState(false);
  const [canGoBack, setCanGoBack] = useState(false);
  const [canGoForward, setCanGoForward] = useState(false);

  const goBack = useCallback(() => {
    if (!canGoBack) {
      return false;
    }

    webViewRef.current?.goBack();
    return true;
  }, [canGoBack]);

  const goForward = useCallback(() => {
    if (canGoForward) {
      webViewRef.current?.goForward();
    }
  }, [canGoForward]);

  const reload = useCallback(() => {
    setLoadFailed(false);
    webViewRef.current?.reload();
  }, []);

  const goHome = useCallback(() => {
    setLoadFailed(false);
    webViewRef.current?.injectJavaScript(`window.location.href = ${JSON.stringify(appUrl)}; true;`);
  }, [appUrl]);

  useEffect(() => {
    const subscription = BackHandler.addEventListener('hardwareBackPress', () => goBack());

    return () => subscription.remove();
  }, [goBack]);

  if (loadFailed) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar style="light" />
        <View style={styles.messagePanel}>
          <Text style={styles.title}>SmartProbook</Text>
          <Text style={styles.message}>
            We could not reach the platform. Please check your internet connection and try again.
          </Text>
          <Pressable style={styles.retryButton} onPress={reload}>
            <Text style={styles.retryButtonText}>Try Again</Text>
          </Pressable>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="light" />
      <View style={styles.navBar}>
        <Pressable
          style={[styles.navButton, !canGoBack && styles.navButtonDisabled]}
          onPress={goBack}
          disabled={!canGoBack}
        >
          <Text style={styles.navButtonText}>Back</Text>
        </Pressable>
        <Pressable
          style={[styles.navButton, !canGoForward && styles.navButtonDisabled]}
          onPress={goForward}
          disabled={!canGoForward}
        >
          <Text style={styles.navButtonText}>Forward</Text>
        </Pressable>
        <Pressable style={styles.navButton} onPress={reload}>
          <Text style={styles.navButtonText}>Refresh</Text>
        </Pressable>
        <Pressable style={[styles.navButton, styles.navButtonGold]} onPress={goHome}>
          <Text style={styles.navButtonGoldText}>Home</Text>
        </Pressable>
      </View>
      <WebView
        ref={webViewRef}
        source={{ uri: appUrl }}
        sharedCookiesEnabled
        thirdPartyCookiesEnabled
        javaScriptEnabled
        domStorageEnabled
        startInLoadingState
        pullToRefreshEnabled
        allowsBackForwardNavigationGestures
        onNavigationStateChange={(navState) => {
          setCanGoBack(navState.canGoBack);
          setCanGoForward(navState.canGoForward);
        }}
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
  navBar: {
    minHeight: 52,
    paddingHorizontal: 10,
    paddingVertical: 8,
    backgroundColor: '#061a44',
    borderBottomColor: 'rgba(215, 169, 40, 0.28)',
    borderBottomWidth: 1,
    flexDirection: 'row',
    gap: 8
  },
  navButton: {
    flex: 1,
    minHeight: 36,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#0f3a8a',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.16)'
  },
  navButtonDisabled: {
    opacity: 0.42
  },
  navButtonGold: {
    backgroundColor: '#d7a928',
    borderColor: '#d7a928'
  },
  navButtonText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 12
  },
  navButtonGoldText: {
    color: '#061a44',
    fontWeight: '900',
    fontSize: 12
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
  },
  retryButton: {
    marginTop: 22,
    minHeight: 44,
    paddingHorizontal: 22,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#d7a928'
  },
  retryButtonText: {
    color: '#061a44',
    fontWeight: '900'
  }
});
