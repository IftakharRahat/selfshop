"use client";

import { SessionProvider } from "next-auth/react";
import type React from "react";

const SessionProviderForNextAuth = ({
	children,
}: {
	children: React.ReactNode;
}) => {
	return <SessionProvider>{children}</SessionProvider>;
};

export default SessionProviderForNextAuth;
