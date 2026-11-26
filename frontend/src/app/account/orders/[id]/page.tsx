import React, { Suspense } from "react";
import { INITIAL_ORDERS } from "@/data/orders";
import OrderDetailView from "./OrderDetailView";
import { Loader2 } from "lucide-react";

interface OrderDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

export function generateStaticParams() {
  const ids = ["1", "2", "3", "4", "5", "placeholder"];
  if (INITIAL_ORDERS && INITIAL_ORDERS.length > 0) {
    INITIAL_ORDERS.forEach((order) => {
      if (order?.id && !ids.includes(order.id)) {
        ids.push(order.id);
      }
    });
  }
  return ids.map((id) => ({
    id,
  }));
}

export default async function OrderDetailPage({ params }: OrderDetailPageProps) {
  const { id } = await params;
  return (
    <Suspense
      fallback={
        <div className="bg-white rounded-3xl border border-[#E7D1CC] p-12 text-center space-y-4">
          <Loader2 className="w-8 h-8 text-[#913638] animate-spin mx-auto" />
          <p className="text-xs text-[#786864]">Fetching tracking & order details...</p>
        </div>
      }
    >
      <OrderDetailView id={id} />
    </Suspense>
  );
}
