
import Footer from "@/components/shared/Footer/Footer";
import Navbar from "@/components/shared/NavBar/NavBar";
import { ReactNode } from "react";

const layout = ({ children }: { children: ReactNode }) => {
  return (
    <div>
      <div className="h-full min-h-[calc(100vh-0px)]">
        <Navbar/>
        {children}
        <Footer/>
        </div>
    </div>
  );
};

export default layout;
