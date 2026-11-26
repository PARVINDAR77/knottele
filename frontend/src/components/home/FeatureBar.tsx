import React from "react";
import { Heart, Sparkles, Leaf, CreditCard, Truck } from "lucide-react";

export function FeatureBar() {
  const features = [
    {
      icon: Heart,
      title: "Unique Designs",
      description: "One of a kind pieces",
    },
    {
      icon: Sparkles,
      title: "Artisan Crafted",
      description: "Handmade with love",
    },
    {
      icon: Leaf,
      title: "Eco-Friendly",
      description: "Sustainable & handmade",
    },
    {
      icon: CreditCard,
      title: "Safe Payments",
      description: "100% secure",
    },
    {
      icon: Truck,
      title: "Pan India Shipping",
      description: "Across all India",
    },
  ];

  return (
    <section className="bg-white border-b border-[#E7D1CC] py-4 sm:py-7">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex sm:grid sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6 lg:gap-8 items-center overflow-x-auto pb-1 sm:pb-0 scrollbar-none snap-x">
          {features.map((feature, index) => {
            const Icon = feature.icon;
            return (
              <div
                key={index}
                className="flex items-center gap-2.5 sm:gap-3 group shrink-0 sm:shrink min-w-[150px] sm:min-w-0 snap-start"
              >
                <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-[#FCE9E5] flex items-center justify-center text-[#913638] shrink-0 group-hover:bg-[#913638] group-hover:text-white transition-colors duration-300">
                  <Icon className="w-4.5 h-4.5 sm:w-5 sm:h-5 stroke-[1.5]" />
                </div>
                <div className="flex flex-col min-w-0">
                  <h4 className="text-xs sm:text-sm font-bold text-[#2E211E] tracking-tight whitespace-nowrap">
                    {feature.title}
                  </h4>
                  <p className="text-[10px] sm:text-[11px] text-[#786864] mt-0.5 leading-snug whitespace-nowrap">
                    {feature.description}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
