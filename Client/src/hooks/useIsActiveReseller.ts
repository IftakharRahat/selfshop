import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useAppSelector } from "@/redux/hooks";

export const useIsActiveReseller = () => {
    const token = useAppSelector((state) => state.auth.access_token);
    const { data: profileData, isLoading, isFetching } = useGetMeQuery(undefined, { skip: !token });

    if (!token) {
        return { isActive: false, isLoading: false, isLoggedIn: false, isExpired: false };
    }

    if (isLoading || isFetching) {
        return { isActive: false, isLoading: true, isLoggedIn: true, isExpired: false };
    }

    const membershipStatus = String(
        profileData?.data?.profile?.membership_status ?? "",
    ).toLowerCase();
    const accountStatus = String(
        profileData?.data?.profile?.status ?? "",
    ).toLowerCase();
    const expireDate = profileData?.data?.profile?.expire_date;

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
    // The backend userProfile() endpoint also auto-marks expired users as Inactive/Unpaid
    const isActive = !isExpired && (membershipStatus === "paid" || accountStatus === "active");

    return { isActive, isLoading: false, isLoggedIn: true, isExpired };
};
