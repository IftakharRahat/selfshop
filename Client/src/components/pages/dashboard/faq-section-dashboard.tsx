/* eslint-disable @typescript-eslint/no-explicit-any */
"use client";

import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { useGetAllFAQsQuery } from "@/redux/features/dashboardApi";

export default function FAQSectionDashboard() {
  const { data } = useGetAllFAQsQuery(undefined);
  const faqs = data?.data || [];

  return (
    <div className="m-4 lg:m-6 md:bg-white rounded-md">
      <div className="md:p-6 h-full">
        {/* Header Section */}
        <div className="text-center mb-12">
          <h1 className="text-4xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h1>
          <p className="text-gray-600 max-w-2xl mx-auto leading-relaxed">
            Lorem ipsum dolor sit amet consectetur. Dignissim erat odio dictum curabitur donec at consequat arcu cursus.
            Eget quis cum amet iaculis orci non.
          </p>
        </div>

        {/* FAQ Accordion */}
        <Accordion type="single" collapsible className="space-y-4 ">
          {faqs.map((faq : any, index : number) => (
            <AccordionItem key={faq.id} value={`item-${faq.id}`} className="border border-gray-200 rounded-lg px-6">
              <AccordionTrigger className="text-left font-medium text-gray-900 hover:no-underline">
                {index + 1}. {faq.question}
              </AccordionTrigger>
              <AccordionContent className="text-gray-600 leading-relaxed space-y-2">
                <p>{faq.answer}</p>
                {/* {faq.youtube_embade && (
                  <div className="mt-2 aspect-video h-fit">
                    <div
                      className="w-full h-fit"
                      dangerouslySetInnerHTML={{ __html: faq.youtube_embade }}
                    />
                  </div>
                )} */}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </div>
  );
}
