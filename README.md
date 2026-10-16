# Gateway Education Services Website 🏫

Official website for **Gateway Education Services** (Kawuku-Bwerenga, Entebbe, Uganda).

## 🚀 Features

- **Responsive Multi-Page Design**: Home, About Us, Admissions, Contact Us, Photo Gallery, and Thank-You pages.
- **Student Admission System**: Online registration form with validation and cloud database storage.
- **Inquiry & Contact Form**: Direct messaging to school administrators.
- **Newsletter Subscription**:
  - Live asynchronous AJAX signup across all page footers.
  - Automatic confirmation email dispatched directly to subscriber inboxes via Gmail SMTP.
  - Redundant backup storage in `data/` and Supabase.
- **Supabase Cloud Database**: Stores submissions in real-time with Row Level Security (RLS) policies.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, Modern JavaScript (Vanilla ES6+), FontAwesome Icons
- **Backend**: PHP 8.x
- **Database**: Supabase (PostgreSQL REST API)
- **Email Delivery**: Pure-PHP RFC-compliant SMTP client with TLS encryption (configured for Gmail)

---

## ⚙️ Local Development Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/tari17-crig/gatewayschool.git
   cd gatewayschool
   ```

2. **Configure Environment Variables**:
   Copy the example environment file and fill in your credentials:
   ```bash
   cp .env.example .env
   ```
   Add your Supabase URL/Key and Gmail App Password:
   ```ini
   SUPABASE_URL=https://qtvmyjgmgmizeglrunjn.supabase.co
   SUPABASE_KEY=your_supabase_key

   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_SECURE=tls
   SMTP_USER=taritech17@gmail.com
   SMTP_PASS=your_google_app_password
   ```

3. **Start the local PHP server**:
   ```bash
   php -S localhost:8000
   ```

4. **Open in browser**:
   Visit [http://localhost:8000](http://localhost:8000)

---

## 📄 License & Attribution

Copyright &copy; 2026 Gateway Education Services. All rights reserved.
