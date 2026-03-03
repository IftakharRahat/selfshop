import { useGetMeQuery } from "@/redux/features/auth/authApi";
import { useAppSelector } from "@/redux/hooks";

export const useIsActiveReseller = () => {
    const token = useAppSelector((state) => state.auth.access_token);
    const { data: profileData, isLoading, isFetching } = useGetMeQuery(undefined, { skip: !token });

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

    // An active reseller is a user with 'paid' membership or 'active' status
    // (Matching logic in WithAuthForAdmin.tsx)
    const isActive = membershipStatus === "paid" || accountStatus === "active";

    return { isActive, isLoading: false, isLoggedIn: true };
};
