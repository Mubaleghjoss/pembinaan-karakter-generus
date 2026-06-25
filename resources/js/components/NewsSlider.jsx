import React from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default function NewsSlider({ news }) {
    let newsData = [];
    try {
        newsData = JSON.parse(news || '[]');
    } catch (e) {
        console.error("Invalid news data", e);
    }

    if (newsData.length === 0) {
        return (
            <div className="w-full h-64 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400">
                Belum ada berita
            </div>
        );
    }

    return (
        <div className="w-full h-[300px] md:h-[500px] rounded-2xl overflow-hidden shadow-2xl group">
            <Swiper
                modules={[Navigation, Pagination, Autoplay]}
                spaceBetween={0}
                slidesPerView={1}
                navigation
                pagination={{ clickable: true }}
                autoplay={{ delay: 5000, disableOnInteraction: false }}
                className="h-full w-full"
            >
                {newsData.map((item, index) => (
                    <SwiperSlide key={index} className="relative">
                        <div className="absolute inset-0 bg-gray-900">
                            <img
                                src={item.cover_path ? `/storage/${item.cover_path}` : 'https://via.placeholder.com/1200x600'}
                                alt={item.judul}
                                className="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-700"
                            />
                        </div>
                        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black via-black/60 to-transparent p-6 md:p-10 text-white">
                            <span className="inline-block px-3 py-1 bg-blue-600 text-xs font-bold rounded-full mb-3">
                                {new Date(item.published_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}
                            </span>
                            <h2 className="text-2xl md:text-4xl font-bold mb-3 leading-tight">{item.judul}</h2>
                            <p className="line-clamp-2 text-gray-300 mb-6 max-w-2xl hidden md:block">{item.excerpt}</p>
                            <a href={`/berita/${item.slug}`} className="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/30 rounded-full transition-all font-medium">
                                Baca Selengkapnya
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14" /><path d="m12 5 7 7-7 7" /></svg>
                            </a>
                        </div>
                    </SwiperSlide>
                ))}
            </Swiper>
        </div>
    );
}
