"use client";
import { useEffect, useRef } from "react";
import { Provider } from "react-redux";
import { persistStore } from "redux-persist";
import { PersistGate } from "redux-persist/integration/react";
import { baseApi } from "@/redux/api/baseApi";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { type AppStore, makeStore } from "@/redux/store";

function ResetApiCacheOnAuthChange() {
	const dispatch = useAppDispatch();
	const accessToken = useAppSelector((state) => state.auth.access_token);
	const previousTokenRef = useRef<string | null | undefined>(undefined);

	useEffect(() => {
		if (previousTokenRef.current === undefined) {
			previousTokenRef.current = accessToken;
			return;
		}

		if (previousTokenRef.current !== accessToken) {
			dispatch(baseApi.util.resetApiState());
			previousTokenRef.current = accessToken;
		}
	}, [accessToken, dispatch]);

	return null;
}

export default function ReduxStoreProvider({
	children,
}: {
	children: React.ReactNode;
}) {
	// const storeRef = useRef<{ store: AppStore; persistor: ReturnType<typeof persistStore> }>();
	const storeRef = useRef<{
		store: AppStore;
		persistor: ReturnType<typeof persistStore>;
	} | null>(null);

	if (!storeRef.current) {
		const store = makeStore();
		const persistor = persistStore(store);
		storeRef.current = { store, persistor };
	}

	return (
		<Provider store={storeRef.current.store}>
			<PersistGate loading={null} persistor={storeRef.current.persistor}>
				<ResetApiCacheOnAuthChange />
				{children}
			</PersistGate>
		</Provider>
	);
}
