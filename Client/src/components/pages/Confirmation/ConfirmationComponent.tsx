"use client"
import React from "react";
import OrderSuccessPage from "./order-success-page";

const ConfirmationComponent = () => {
  const handleSaveProduct = () => {
    // Handle save product logic
    console.log("Product saved!");
  };
  return (
    <>
      <OrderSuccessPage orderId="SS00142" customerPhone="01976367981" onSaveProduct={handleSaveProduct} />
    </>
  );
};

export default ConfirmationComponent;
