from flask import Flask, render_template, request, redirect, url_for, flash
from flask_login import LoginManager, UserMixin, login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash, check_password_hash
import sqlite3
from datetime import datetime


app = Flask(__name__)
app.secret_key = 'expedientlog-secret-key-2025'  # Change in production!


@app.route('/')
def landing():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    return render_template('landing.html')

login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

class User(UserMixin):
    def __init__(self, id, username):
        self.id = id
        self.username = username

@login_manager.user_loader
def load_user(user_id):
    conn = get_db()
    cur = conn.cursor()
    cur.execute("SELECT id, username FROM users WHERE id = ?", (user_id,))
    row = cur.fetchone()
    if row:
        return User(row[0], row[1])
    return None

def get_db():
    conn = sqlite3.connect('expedientlog.db')
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    with app.app_context():
        db = get_db()
        with open('schema.sql', 'r') as f:
            db.cursor().executescript(f.read())
        db.commit()

init_db()

@app.route('/')
def index():
    if current_user.is_authenticated:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username'].strip()
        password = request.form['password']
        if len(username) < 3:
            flash('Username must be at least 3 characters', 'danger')
            return render_template('register.html')
        if len(password) < 6:
            flash('Password must be at least 6 characters', 'danger')
            return render_template('register.html')

        hashed = generate_password_hash(password)
        conn = get_db()
        cur = conn.cursor()
        try:
            cur.execute("INSERT INTO users (username, password) VALUES (?, ?)", (username, hashed))
            conn.commit()
            flash('Account created! Welcome to ExpedientLog', 'success')
            return redirect(url_for('login'))
        except sqlite3.IntegrityError:
            flash('Username already taken!', 'danger')
        finally:
            conn.close()
    return render_template('register.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username'].strip()
        password = request.form['password']
        conn = get_db()
        cur = conn.cursor()
        cur.execute("SELECT id, username, password FROM users WHERE username = ?", (username,))
        row = cur.fetchone()
        if row and check_password_hash(row['password'], password):
            user = User(row['id'], row['username'])
            login_user(user, remember=True)
            return redirect(url_for('dashboard'))
        flash('Invalid credentials', 'danger')
        conn.close()
    return render_template('login.html')

@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('Logged out of ExpedientLog', 'info')
    return redirect(url_for('login'))

@app.route('/dashboard', methods=['GET', 'POST'])
@login_required
def dashboard():
    if request.method == 'POST':
        task = request.form['task'].strip()
        if task and len(task) <= 500:
            conn = get_db()
            cur = conn.cursor()
            cur.execute("INSERT INTO tickets (user_id, task) VALUES (?, ?)",
                        (current_user.id, task))
            conn.commit()
            conn.close()
            flash('Task logged instantly!', 'success')
        else:
            flash('Task is empty or too long (max 500 chars)', 'warning')

    today = datetime.now().strftime('%Y-%m-%d')
    conn = get_db()
    cur = conn.cursor()
    cur.execute("""
        SELECT task, created_at FROM tickets 
        WHERE user_id = ? AND date(created_at) = ?
        ORDER BY created_at DESC
    """, (current_user.id, today))
    today_tasks = cur.fetchall()
    conn.close()

    return render_template('dashboard.html', tasks=today_tasks, today=today)

@app.route('/history')
@login_required
def history():
    page = request.args.get('page', 1, type=int)
    per_page = 25
    offset = (page - 1) * per_page

    conn = get_db()
    cur = conn.cursor()
    cur.execute("""
        SELECT task, created_at FROM tickets 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    """, (current_user.id, per_page, offset))
    tasks = cur.fetchall()

    cur.execute("SELECT COUNT(*) FROM tickets WHERE user_id = ?", (current_user.id,))
    total = cur.fetchone()[0]
    conn.close()

    return render_template('history.html', tasks=tasks, page=page,
                           total_pages=(total + per_page - 1) // per_page)

