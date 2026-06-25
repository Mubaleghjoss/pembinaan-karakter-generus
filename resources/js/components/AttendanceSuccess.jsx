import React, { useEffect } from 'react';
import { CheckCircle } from 'lucide-react';

export default function AttendanceSuccess({ data, onClose }) {
    useEffect(() => {
        const timer = setTimeout(() => {
            onClose();
        }, 5000);
        return () => clearTimeout(timer);
    }, [onClose]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-in fade-in zoom-in duration-300">
                <div className="bg-green-500 p-8 flex justify-center relative overflow-hidden">
                    <div className="absolute inset-0 bg-white/10 transform -skew-y-12 scale-150"></div>
                    <CheckCircle className="w-24 h-24 text-white relative z-10 drop-shadow-lg" />
                </div>
                <div className="p-8 text-center">
                    <h3 className="text-2xl font-bold text-gray-800 mb-2">Alhamdulillah!</h3>
                    <p className="text-green-600 font-medium text-lg mb-6 leading-relaxed">{data.message}</p>

                    {data.student && (
                        <div className="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100 shadow-inner">
                            {data.student.foto && (
                                <img
                                    src={data.student.foto}
                                    alt={data.student.nama}
                                    className="w-20 h-20 rounded-full mx-auto mb-3 object-cover border-4 border-white shadow-md"
                                />
                            )}
                            <h4 className="font-bold text-gray-900 text-lg">{data.student.nama}</h4>
                            <p className="text-gray-500">{data.student.nis}</p>
                            <div className="mt-2 inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                {data.student.jam}
                            </div>
                        </div>
                    )}

                    <button
                        onClick={onClose}
                        className="w-full py-3 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                    >
                        Tutup (Otomatis 5s)
                    </button>
                </div>
            </div>
        </div>
    );
}
