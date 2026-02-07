/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { useState } from "react";
import { Input, Button, Alert, Card, ConfigProvider, Modal, Form } from "antd";
import {
  useCreateStoreFraudNumberMutation,
  useGetCheckFraudQuery,
} from "@/redux/features/fraudCustomer/fraudCustomerApi";

export default function FraudChecker() {
  const [orderId, setOrderId] = useState("");
  const [phoneNumber, setPhoneNumber] = useState("");
  const [isModalOpen, setIsModalOpen] = useState(false);

  const { data, isFetching } = useGetCheckFraudQuery(phoneNumber, {
    skip: !phoneNumber,
  });

  const [createStoreFraudNumber] = useCreateStoreFraudNumberMutation();

  const handleSearch = () => {
    setPhoneNumber(orderId);
  };

  const handleCreate = async (values: any) => {
    await createStoreFraudNumber(values);
    setIsModalOpen(false);
  };

  return (
    <div className="m-4 lg:m-6 md:bg-white rounded-md">
      <div className="w-full md:shadow-sm md:p-8">
        <h1 className="text-2xl font-semibold text-gray-900 mb-8">Fraud checker</h1>

        <div className="space-y-6">
          <div className="space-y-2">
            <label htmlFor="orderId" className="text-sm font-medium text-gray-700">
              Phone number
            </label>
            <ConfigProvider
              theme={{
                token: {
                  colorPrimary: "#E5005F",
                },
              }}
            >
              <Input
                id="orderId"
                placeholder="Enter the phone number"
                value={orderId}
                onChange={(e) => setOrderId(e.target.value)}
                size="large"
              />
            </ConfigProvider>
          </div>

          <Button
            type="primary"
            size="large"
            block
            style={{ backgroundColor: "#E5005F" }}
            onClick={handleSearch}
          >
            Check
          </Button>

          {/* Show Fraud Results */}
          {!isFetching && data?.data?.length > 0 && (
            <Card title="Fraud Record Found" className="mt-4">
              {data.data.map((item: any) => (
                <div key={item.id} className="mb-3">
                  <p><strong>Phone:</strong> {item.phone}</p>
                  <p><strong>Message:</strong> {item.message}</p>
                  <p><strong>Status:</strong> {item.status}</p>
                  <p><strong>Date:</strong> {new Date(item.created_at).toLocaleString()}</p>
                </div>
              ))}
            </Card>
          )}

          {/* If no match */}
          {!isFetching && phoneNumber && data?.data?.length === 0 && (
            <Alert
              message="No fraud record found."
              type="info"
              showIcon
              className="mt-4"
            />
          )}

          {/* Add New Fraud Button */}
          <Button   style={{ backgroundColor: "#E5005F" }} onClick={() => setIsModalOpen(true)} className="mt-4 bg-[#E91E63] hover:bg-[#C2185B] !text-white" type="default">
            + Report Fraud
          </Button>

          {/* Modal */}
          <Modal
            title="Report Fraud Number"
            open={isModalOpen}
            onCancel={() => setIsModalOpen(false)}
            footer={null}
          >
            <Form layout="vertical" onFinish={handleCreate}>
              <Form.Item
                label="Phone"
                name="phone"
                rules={[{ required: true, message: "Phone is required" }]}
              >
                <Input placeholder="Enter phone number" />
              </Form.Item>

              <Form.Item
                label="Message"
                name="message"
                rules={[{ required: true, message: "Message is required" }]}
              >
                <Input.TextArea rows={3} placeholder="Enter message" />
              </Form.Item>

              <Button
                type="primary"
                htmlType="submit"
                block
                style={{ backgroundColor: "#E5005F" }}
              >
                Submit
              </Button>
            </Form>
          </Modal>
        </div>
      </div>
    </div>
  );
}
