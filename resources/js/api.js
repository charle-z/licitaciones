import axios from "axios";

export const api = axios.create({
  baseURL: "/api",
  timeout: 30000,
  headers: {
    "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]').content,
  },
});

export function errorMessage(error) {
  return (
    error.response?.data?.message ||
    "No se pudo conectar con el servidor. Inténtalo de nuevo."
  );
}

export function dateLabel(date, time = "") {
  if (!date) return "—";
  const [year, month, day] = date.substring(0, 10).split("-");
  return `${day}/${month}/${year}${time ? " · " + time.substring(0, 5) : ""}`;
}
