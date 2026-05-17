"use client";
import { useEffect } from "react";

const ErrorPage = ({
	error,
	reset,
}: {
	error: Error & { digest?: string };
	reset: () => void;
}) => {
	useEffect(() => {
		// Automatically reload the page if a new deployment removed old JS chunks
		if (error.message && error.message.toLowerCase().includes("failed to load chunk")) {
			window.location.reload();
		}
	}, [error]);

	return (
		<div className="mt-10  text-center">
			<p className="text-4xl bg-red-500 text-white p-5 w-[50%] mx-auto rounded-xl">
				Something went wrong!!!
			</p>
			<p className="text-4xl bg-red-500 text-white p-5 w-[50%] mx-auto rounded-xl mt-2">
				{error.message}
			</p>
			<button
				onClick={() => reset()}
				className="btn btn-error btn-outline mt-5"
			>
				Try Again
			</button>
		</div>
	);
};

export default ErrorPage;
