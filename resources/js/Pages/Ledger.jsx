import React from "react";
import { usePage, router, Link } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import PageHeader from "@/Components/PageHeader";

export default function Ledger() {
    const { props } = usePage();
    const {
        year,
        openingBalance,
        closingBalance,
        opening_interest,
        totalDeposit,
        totalInterest,
        totalWithdrawal,
        closing,
        ledgerData = [],
        interestFinalized,
    } = props;

    // Back button handler
    const handleBack = (e) => {
        e.preventDefault();
        if (document.referrer) {
            window.history.back();
        } else {
            router.visit(route("ledger.years"));
        }
    };

    return (
        <AppLayout title={`Ledger ${year || ""}`}>
            {/* Header */}
            <PageHeader
                title={`Ledger ${year ? `(${year})` : ""}`}
                subtitle="Financial summary from April to March"
            />

            <div className="p-3 space-y-3">
                {/* Opening Balance */}
                <div className="bg-white rounded-md shadow-sm ">
                    <div className="mb-3">
                        <Link
                            href={route("ledger.years")}
                            className="text-indigo-600 text-sm font-semibold hover:underline"
                        >
                            ← Back
                        </Link>
                    </div>

                    {/* Side by side section */}
                    <div className="bg-gray-50 border border-gray-200 rounded-md shadow-sm p-3 text-sm">
                        <div className="grid grid-cols-2 gap-3 ">
                            <div>
                                <h2 className="text-gray-600 text-xs sm:text-sm font-semibold">
                                    Opening Balance
                                </h2>
                                <p className="text-indigo-700 text-base font-bold mt-1">
                                    ₹{" "}
                                    {Number(
                                        openingBalance ?? 0,
                                    ).toLocaleString()}
                                </p>
                            </div>

                            <div>
                                <h2 className="text-gray-600 text-xs  font-semibold">
                                    Interest on Opening Amount{" "}
                                    <span className="text-red-900 ">*</span>
                                </h2>
                                <p className="text-indigo-700 text-base font-bold mt-1">
                                    {interestFinalized
                                        ? `₹ ${Number(opening_interest ?? 0).toLocaleString()}`
                                        : "To be updated"}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Ledger Table */}
                <div className="bg-white shadow-sm rounded-md overflow-auto">
                    <table className="w-full text-xs sm:text-sm text-gray-700 border-collapse border border-gray-200">
                        <thead className="bg-indigo-600 text-white">
                            <tr>
                                <th className="py-2 px-3 text-left border-r border-indigo-400">
                                    Month
                                </th>
                                <th className="py-2 px-3 text-left border-r border-indigo-400">
                                    CGR
                                </th>
                                <th className="py-2 px-3 text-left border-r border-indigo-400">
                                    Mode
                                </th>
                                <th className="py-2 px-3 text-left">
                                    Interest <span className=" ">*</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {ledgerData.length > 0 ? (
                                ledgerData.map((monthData, mIdx) =>
                                    monthData.rows.map((row, rIdx) => {
                                        // background stripe per month
                                        const monthBg =
                                            mIdx % 2
                                                ? "bg-gray-50"
                                                : "bg-white";

                                        // add bottom border when month block ends
                                        const isLastRowOfMonth =
                                            rIdx === monthData.rows.length - 1;
                                        const bottomBorder = isLastRowOfMonth
                                            ? "border-b-2 border-gray-300"
                                            : "border-b border-gray-200";

                                        return (
                                            <tr
                                                key={`${mIdx}-${rIdx}`}
                                                className={`${monthBg} ${bottomBorder}`}
                                            >
                                                {rIdx === 0 && (
                                                    <td
                                                        rowSpan={
                                                            monthData.rows
                                                                .length
                                                        }
                                                        className="py-2 px-3 font-bold align-center  border-r border-gray-200"
                                                    >
                                                        {monthData.month}
                                                    </td>
                                                )}

                                                <td className="py-2 px-3 border-r border-gray-200">
                                                    <p>
                                                        ₹{" "}
                                                        {Number(
                                                            row.amount,
                                                        ).toLocaleString()}
                                                    </p>
                                                    {row.date && (
                                                        <p className="text-[10px] text-gray-500">
                                                            {row.date}
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="py-2 px-3 border-r border-gray-200">
                                                    {row.mode ?? "-"}
                                                </td>

                                                {/* Interest */}
                                                <td className="py-2 px-3">
                                                    {interestFinalized
                                                        ? `₹ ${Number(row.interest ?? 0).toLocaleString()}`
                                                        : "To be updated"}
                                                </td>
                                            </tr>
                                        );
                                    }),
                                )
                            ) : (
                                <tr>
                                    <td
                                        colSpan="4"
                                        className="text-center text-gray-500 py-4 border-t border-gray-200"
                                    >
                                        No ledger data available
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Closing Balance */}
                <div className="bg-gray-50 border border-gray-200 rounded-md shadow-sm p-3 text-sm">
                    <div className="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <h2 className="text-gray-600 font-semibold text-xs">
                                Total Deposit
                            </h2>
                            <p className="text-indigo-700 font-bold mt-1">
                                ₹ {Number(totalDeposit ?? 0).toLocaleString()}
                            </p>
                        </div>

                        <div>
                            <h2 className="text-gray-600 font-semibold text-xs">
                                Total Interest
                            </h2>
                            <p className="text-indigo-700 font-bold mt-1">
                                {interestFinalized
                                    ? `₹ ${Number(totalInterest ?? 0).toLocaleString()}`
                                    : "To be updated"}
                            </p>
                        </div>

                        <div>
                            <h2 className="text-gray-600 font-semibold text-xs">
                                Total Withdrawal
                            </h2>
                            <p className="text-indigo-700 font-bold mt-1">
                                ₹ {(totalWithdrawal ?? 0).toLocaleString()}
                            </p>
                        </div>

                        <div>
                            <h2 className="text-gray-600 font-semibold text-xs">
                                Closing Balance
                            </h2>
                            <p className="text-indigo-700 font-bold mt-1">
                                {interestFinalized
                                    ? `₹ ${Number(closing ?? 0).toLocaleString()} *`
                                    : "To be updated"
                            </p>
                        </div>
                    </div>
                    <div className="">
                        <span className="text-red-900 text-xs">
                            * All the interest amount shown above are for
                            reference only. The interest amount is valid only
                            after end of financial year.
                        </span>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
