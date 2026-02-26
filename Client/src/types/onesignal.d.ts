export {};

declare global {
	interface OneSignalInitOptions {
		appId: string;
		allowLocalhostAsSecureOrigin?: boolean;
		notifyButton?: { enable?: boolean };
		serviceWorkerPath?: string;
		serviceWorkerParam?: { scope?: string };
		safari_web_id?: string;
	}

	interface OneSignalForegroundEvent {
		notification: {
			title?: string;
			body?: string;
			additionalData?: Record<string, unknown>;
		};
	}

	interface OneSignalInstance {
		init: (options: OneSignalInitOptions) => Promise<void>;
		login: (externalId: string) => Promise<void>;
		logout: () => Promise<void>;
		User: {
			addTag: (key: string, value: string) => Promise<void>;
		};
		Notifications: {
			isPushSupported: () => boolean;
			permission?: boolean;
			requestPermission: () => Promise<void>;
			addEventListener: (
				event: "foregroundWillDisplay",
				listener: (event: OneSignalForegroundEvent) => void,
			) => void;
		};
	}

	interface Window {
		OneSignalDeferred?: Array<(OneSignal: OneSignalInstance) => void | Promise<void>>;
		__oneSignalInitPromise?: Promise<void>;
		__oneSignalInitializedAppId?: string;
		__oneSignalForegroundListenerBound?: boolean;
	}
}
