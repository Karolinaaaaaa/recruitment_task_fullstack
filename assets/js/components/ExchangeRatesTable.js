import React, { Component } from "react";
import axios from "axios";

class ExchangeRatesTable extends Component {
    constructor() {
        super();
        const urlParams = new URLSearchParams(window.location.search);
        const initialDate = urlParams.get("date") || new Date().toISOString().split("T")[0];

        this.state = {
            rates: [],
            todayRates: [],
            date: initialDate,
            loading: true,
            error: null,
        };
    }

    componentDidMount() {
        this.fetchExchangeRates();
    }

    fetchExchangeRates() {
        const baseUrl = "http://telemedi-zadanie.localhost";
        const { date } = this.state;

        const today = new Date().toISOString().split("T")[0];
        const requests = [
            axios.get(`${baseUrl}/api/exchange-rates?date=${date}`),
            axios.get(`${baseUrl}/api/exchange-rates?date=${today}`),
        ];

        Promise.all(requests)
            .then(([selectedResponse, todayResponse]) => {
                const selectedRates = selectedResponse.data.data;
                const todayRates = todayResponse.data.data;

                const errorToday = selectedRates.some((rate) =>
                    rate.error?.includes("dzisiejszej daty")
                );

                this.setState({
                    rates: selectedRates,
                    todayRates: todayRates,
                    loading: false,
                    error: errorToday ? "Dane dla dzisiejszej daty jeszcze nie są dostępne." : null,
                });
            })
            .catch(() => {
                this.setState({
                    loading: false,
                    error: "Nie udało się pobrać danych z API NBP.",
                });
            });
    }

    handleDateChange(event) {
        const selectedDate = event.target.value;
        this.setState({ date: selectedDate, loading: true, error: null }, () => {

            const url = new URL(window.location);
            url.searchParams.set("date", selectedDate);
            window.history.pushState({}, "", url);

            this.fetchExchangeRates();
        });
    }

    render() {
        const { rates, todayRates, loading, error, date } = this.state;
        const isToday = new Date().toISOString().split("T")[0] === date;

        return (
            <div>
                <section className="row-section">
                    <div className="container">
                        <div className="row mt-5">
                            <div className="col-12">
                                <h2 className="text-center">
                                    <span>Kursy Walut</span>
                                </h2>

                                <div className="text-center mb-4">
                                    <label htmlFor="date-picker">Wybierz datę</label>
                                    <input
                                        id="date-picker"
                                        type="date"
                                        value={date}
                                        min="2023-01-01"
                                        max={new Date().toISOString().split("T")[0]}
                                        onChange={(event) => this.handleDateChange(event)}
                                        className="form-control"
                                    />
                                </div>

                                {error && (
                                    <div className="alert alert-warning text-center">
                                        <h5>{error}</h5>
                                    </div>
                                )}

                                {loading ? (
                                    <div className="text-center">
                                        <span className="fa fa-spin fa-spinner fa-4x"></span>
                                    </div>
                                ) : (
                                    <div className="table-responsive">
                                        <table className="table table-striped">
                                            <thead className="thead-dark">
                                            <tr>
                                                <th>Waluta</th>
                                                <th>Średni Kurs ({date})</th>
                                                <th>Kurs Kupna ({date})</th>
                                                <th>Kurs Sprzedaży ({date})</th>
                                                {!isToday && <th>Średni Kurs (Dzisiejsza data)</th>}
                                                {!isToday && <th>Kurs Kupna (Dzisiejsza data)</th>}
                                                {!isToday && <th>Kurs Sprzedaży (Dzisiejsza data)</th>}
                                            </tr>
                                            </thead>
                                            <tbody>
                                            {rates.map((rate, index) => {
                                                const todayRate = todayRates[index] || {};
                                                return (
                                                    <tr key={rate.currency}>
                                                        <td>
                                                            {rate.name} ({rate.currency})
                                                        </td>
                                                        <td>{rate.averageRate?.toFixed(4) || ''}</td>
                                                        <td>{rate.buyRate?.toFixed(4) || ''}</td>
                                                        <td>{rate.sellRate?.toFixed(4) || ''}</td>
                                                        {!isToday && (
                                                            <>
                                                                <td>{todayRate.averageRate?.toFixed(4) || ''}</td>
                                                                <td>{todayRate.buyRate?.toFixed(4) || ''}</td>
                                                                <td>{todayRate.sellRate?.toFixed(4) || ''}</td>
                                                            </>
                                                        )}
                                                    </tr>
                                                );
                                            })}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        );
    }
}

export default ExchangeRatesTable;
