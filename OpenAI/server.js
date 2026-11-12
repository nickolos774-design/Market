import express from "express";
import cors from "cors";
import 'dotenv/config';
import fetch from "node-fetch";

const app = express();

// CORS для фронтенда
app.use(cors({
  origin: "http://localhost",  // XAMPP порт
  methods: ["GET", "POST", "OPTIONS"],
  allowedHeaders: ["Content-Type", "Authorization"]
}));

app.use(express.json());

// ==== Проверка работы сервера ====
app.get("/", (req, res) => {
  console.log("GET / получен");
  res.send("Server работает!");
});

// ==== Маршрут чата ====
app.post("/chat", async (req, res) => {
  console.log("REQ BODY:", req.body);

  try {
    const response = await fetch("https://openrouter.ai/api/v1/chat/completions", {
      method: "POST",
      headers: {
        "Authorization": `Bearer ${process.env.OPENROUTER_API_KEY}`,
        "Content-Type": "application/json"
      },
      body: JSON.stringify(req.body)
    });

    console.log("RESPONSE STATUS:", response.status);

    const data = await response.json();
    console.log("API RESPONSE:", data);

    res.json(data);
  } catch (err) {
    console.error("SERVER ERROR:", err);
    res.status(500).json({ error: "Server error", details: err.message });
  }
});

const PORT = 3000;
app.listen(PORT, () => console.log(`Server działa na http://localhost:${PORT}`));