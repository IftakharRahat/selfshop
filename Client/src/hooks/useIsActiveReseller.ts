import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useAppSelector } from "@/redux/hooks";
import { useEffect, useRef, useCallback } from "react";
import { useDispatch } from "react-redux";
import { logout } from "@/redux/features/auth/authSlice";
import { toast } from "sonner";

export const useIsActiveReseller = () => {
    const token = useAppSelector((state) => state.auth.access_token);
    const dispatch = useDispatch();
    const { data: profileData, isLoading, isFetching, refetch } = useGetMeQuery(undefined, { skip: !token });
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const handleExpired = useCallback(() => {
        toast.error("Your account has expired. Please renew your subscription to continue.");
        dispatch(logout());
    }, [dispatch]);

    const expireDate = profileData?.data?.profile?.expire_date;

    useEffect(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }

        if (!token || !expireDate) return;

        // Parse the expire date — handle both YYYY-MM-DD and other formats
        const parsed = new Date(expireDate);
        if (isNaN(parsed.getTime())) return; // Invalid date, skip

        // Set expiry to end of day
        parsed.setHours(23, 59, 59, 999);
        const expiryTime = parsed.getTime();
        const now = Date.now();
        const timeLeft = expiryTime - now;

        if (timeLeft <= 0) {
            // Already expired — force refetch to let backend auto-mark as Inactive/Unpaid
            refetch();
            return;
        }

        // Set a timer to trigger logout exactly when the account expires
        timerRef.current = setTimeout(() => {
            handleExpired();
        }, timeLeft);

        return () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
                timerRef.current = null;
            }
        };
    }, [token, expireDate, handleExpired, refetch]);

    if (!token) {
        return { isActive: false, isLoading: false, isLoggedIn: false };
    }

    if (isLoading || isFetching) {
        return { isActive: false, isLoading: true, isLoggedIn: true };
    }

    const membershipStatus = String(
        profileData?.data?.profile?.membership_status ?? "",
    ).toLowerCase();
    const accountStatus = String(
        profileData?.data?.profile?.status ?? "",
    ).toLowerCase();

    // Check if account has expired using proper date parsing
    let isExpired = false;
    if (expireDate) {
        const parsed = new Date(expireDate);
        if (!isNaN(parsed.getTime())) {
            parsed.setHours(23, 59, 59, 999);
            isExpired = parsed.getTime() < Date.now();
        }
    }

    // An active reseller must have a valid (non-expired) paid membership or active status
    const isActive = !isExpired && (membershipStatus === "paid" || accountStatus === "active");

    return { isActive, isLoading: false, isLoggedIn: true, isExpired };
};
