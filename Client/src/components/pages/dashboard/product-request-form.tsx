/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import React, { useState } from "react";
import { useForm, Controller } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Upload, X } from "lucide-react";
import { ConfigProvider, Input, Select } from "antd";
import "antd/dist/reset.css";
import TextArea from "antd/es/input/TextArea";
import { useCreateRequestProductMutation, useGetAllRequestProductsQuery } from "@/redux/features/requestProductListApi";
import { handleAsyncWithToast } from "@/utils/handleAsyncWithToast";

const productRequestSchema = z.object({
  productName: z.string().min(1, "Product name is required"),
  description: z.string().min(1, "Description is required"),
  quantity: z.string().min(1, "Product quantity is required"),
  image: z.custom<File | undefined>((val) => val === undefined || val instanceof File, {
    message: "Image is required",
  }),
});

type ProductRequestFormValues = z.infer<typeof productRequestSchema>;

export default function ProductRequestForm() {
  const {
    control,
    handleSubmit,
    setValue,
    formState: { errors },
    reset,
  } = useForm<ProductRequestFormValues>({
    resolver: zodResolver(productRequestSchema),
    defaultValues: {
      productName: "",
      description: "",
      quantity: "",
      image: undefined,
    },
  });

  const [preview, setPreview] = useState<string | null>(null);
  const [createRequestProduct] = useCreateRequestProductMutation();
  const { data } = useGetAllRequestProductsQuery(undefined);

  const onSubmit = async (formDataValues: ProductRequestFormValues) => {
    if (!formDataValues.image) {
      alert("Please upload an image before submitting.");
      return;
    }

    const formData = new FormData();
    formData.append("p_name", formDataValues.productName);
    formData.append("attachment", formDataValues.image);
    formData.append("p_quantity", formDataValues.quantity);
    formData.append("p_description", formDataValues.description);

    try {
      await handleAsyncWithToast(async () => {
        return createRequestProduct(formData);
      });
      reset();
      setPreview(null);
    } catch (error) {
      console.error("Upload failed:", error);
    }
  };

  const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      setValue("image", file, { shouldValidate: true });
      setPreview(URL.createObjectURL(file));
    }
  };

  const handleImageRemove = () => {
    setValue("image", undefined, { shouldValidate: true });
    setPreview(null);
  };

  return (
    <div className=" bg-gray-50 ms:p-4 md:m-8 rounded-md">
      <div className=" mx-auto bg-white shadow rounded-lg p-5 md:p-8">
        <h1 className="text-2xl font-semibold text-gray-900 mb-8">Product request list</h1>

        <ConfigProvider
          theme={{
            token: {
              colorPrimary: "#E5005F",
            },
          }}
        >
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
            {/* Product Name */}
            <div>
              <p className="text-sm font-medium text-gray-700 h-3">Product name</p>
              <Controller
                name="productName"
                control={control}
                render={({ field }) => (
                  <Input
                    size="large"
                    placeholder="Enter the product name"
                    {...field}
                    className="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-pink-500"
                  />
                )}
              />
              {errors.productName && <p className="text-sm text-red-500">{errors.productName.message}</p>}
            </div>

            {/* Description */}
            <div className="space-y-2">
              <p className="text-sm font-medium text-gray-700 h-3">Description</p>
              <Controller
                name="description"
                control={control}
                render={({ field }) => (
                  <TextArea
                    rows={4}
                    placeholder="Enter the message..."
                    {...field}
                    className="w-full min-h-[120px] border rounded-md p-2 resize-none focus:outline-none focus:ring-2 focus:ring-pink-500"
                  />
                )}
              />
              {errors.description && <p className="text-sm text-red-500">{errors.description.message}</p>}
            </div>

            <div className="space-y-2">
              <p className="text-sm font-medium text-gray-700 h-3">Product quantity</p>
              <Controller
                control={control}
                name="quantity"
                render={({ field }) => (
                  <Select
                    size="large"
                    {...field}
                    placeholder="Enter the product quantity"
                    className="w-full"
                    onChange={(value) => field.onChange(value)}
                    options={[
                      { value: "1", label: "1" },
                      { value: "2", label: "2" },
                      { value: "3", label: "3" },
                      { value: "4", label: "4" },
                      { value: "5", label: "5" },
                      { value: "10", label: "10" },
                      { value: "20", label: "20" },
                      { value: "50", label: "50" },
                      { value: "100", label: "100+" },
                    ]}
                  />
                )}
              />
              {errors.quantity && <p className="text-sm text-red-500">{errors.quantity.message}</p>}
            </div>

            <div className="space-y-2">
              <p className="text-sm font-medium text-gray-700 h-3">Upload image</p>
              <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition-colors">
                <div className="flex flex-col items-center space-y-4">
                  <Upload className="w-12 h-12 text-gray-400" />
                  <div className="space-y-2">
                    <p className="text-gray-500">Upload image</p>
                    <input type="file" accept="image/*" onChange={handleImageUpload} className="hidden" id="image-upload" />
                    <button
                      type="button"
                      onClick={() => document.getElementById("image-upload")?.click()}
                      className="px-4 py-2 border rounded-md text-gray-600 border-gray-300 hover:bg-gray-100"
                    >
                      Choose image
                    </button>
                  </div>
                  {preview && (
                    <div className="relative mt-4">
                      <img src={preview} alt="Preview" className="max-h-32 rounded-lg border" />
                      <button
                        type="button"
                        onClick={handleImageRemove}
                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                      >
                        <X size={14} />
                      </button>
                    </div>
                  )}
                </div>
              </div>
              {errors.image && <p className="text-sm text-red-500">{errors.image.message}</p>}
            </div>

            <button type="submit" className="w-full bg-[#E5005F] hover:bg-pink-600 !text-white py-3 text-base font-medium rounded-md cursor-pointer">
              Upload now
            </button>
          </form>
        </ConfigProvider>
      </div>

      {/* Requested Products Table */}
      <div className="w-full m-4 lg:m-6 md:bg-white rounded-md md:p-8">
        <div className="p-0">
          <h2 className="text-lg font-semibold text-gray-900">Requested Products</h2>
          <div className="flex items-center justify-between p-4 border-b border-gray-200">
            <h2 className="text-sm font-medium text-gray-900">All Requests</h2>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">ID</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Product Name</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Image</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                  <th className="text-left p-4 text-sm font-medium text-gray-600">Date</th>
                </tr>
              </thead>

              <tbody>
                {!data?.data ? (
                  <tr>
                    <td colSpan={5} className="p-4 text-center text-sm text-gray-500">
                      Loading requested products...
                    </td>
                  </tr>
                ) : data.data.length > 0 ? (
                  data.data.map((item: any) => (
                    <tr key={item.id} className="border-b border-gray-100 hover:bg-gray-50">
                      <td className="p-4 text-sm text-gray-900">#{item.id}</td>
                      <td className="p-4 text-sm text-gray-900">{item.p_name}</td>
                      <td className="p-4 text-sm text-gray-600">
                        <img
                          src={"https://api-v1.selfshop.com.bd" + "/" + item.attachment}
                          alt="product"
                          className="w-14 h-14 rounded object-cover"
                        />
                      </td>
                      <td className="p-4 text-sm text-gray-600">
                        <span
                          className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
                            item.status === "Paid"
                              ? "bg-green-100 text-green-800"
                              : item.status === "Pending"
                              ? "bg-yellow-100 text-yellow-800"
                              : "bg-red-100 text-red-800"
                          }`}
                        >
                          {item.status}
                        </span>
                      </td>
                      <td className="p-4 text-sm text-gray-600">{new Date(item.created_at).toLocaleDateString()}</td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="p-4 text-center text-sm text-gray-500">
                      No requested products found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
