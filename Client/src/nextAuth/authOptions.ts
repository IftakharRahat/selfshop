import type { NextAuthOptions } from "next-auth";
import AppleProvider from "next-auth/providers/apple";
import FacebookProvider from "next-auth/providers/facebook";
import GoogleProvider from "next-auth/providers/google";

export const authOptions: NextAuthOptions = {
	// Configure one or more authentication providers
	providers: [
		GoogleProvider({
			clientId: process.env.NEXT_PUBLIC_GOOGLE_ID as string,
			clientSecret: process.env.NEXT_PUBLIC_GOOGLE_SECRET as string,
		}),
		FacebookProvider({
			clientId: process.env.NEXT_PUBLIC_FACEBOOK_ID as string,
			clientSecret: process.env.NEXT_PUBLIC_FACEBOOK_SECRET as string,
		}),
		AppleProvider({
			clientId: process.env.NEXT_PUBLIC_APPLE_ID as string,
			clientSecret: process.env.NEXT_PUBLIC_APPLE_SECRET as string,
		}),
	],

	secret: process.env.NEXT_PUBLIC_NEXT_AUTH_SECRET,
};
