from flask import Flask, render_template, request, redirect, url_for, flash, session

from src.telegram import send_telegram_notification_async, getDebug, getSecretKey


app = Flask(__name__)
app.secret_key = getSecretKey()

MESSAGES = {
    "ru": {
        200: "Заявка успешно отправлена. Мы свяжемся с вами в ближайшее время.",
        400: "Ошибка: некорректно заполнены имя или номер телефона."
    },

    "kk": {
        200: "Өтінім сәтті жіберілді. Жақын арада сізбен хабарласамыз.",
        400: "Қате: аты-жөніңіз немесе телефон нөміріңіз дұрыс толтырылмаған."
    },

    "en": {
        200: "Your request has been sent successfully. We will contact you soon.",
        400: "Error: the name or phone number was entered incorrectly."
    }
}


@app.route("/", methods=["GET", "POST"])
def landing():
    if request.method == "POST":
        # Get language from session or cookie, fallback to accept-language
        language = session.get('language') or request.cookies.get('language') or request.accept_languages.best_match(["ru", "kk", "en"]) or "ru"
        
        name = request.form.get("name")
        email = request.form.get("email")
        phone = request.form.get("phone")
        print_size = request.form.get("print-size")
        comment = request.form.get("comment", "")

        if name and phone:
            send_telegram_notification_async(name=name, email=email, phone=phone, print_size=print_size, comment=comment)
            flash(MESSAGES[language][200], 'success')
        else:
            flash(MESSAGES[language][400], 'error')
        
        # Redirect to prevent duplicate submissions (Post/Redirect/Get pattern)
        return redirect(url_for('landing'))

    return render_template("landing.html")

@app.route("/about-us")
def aboutUs():
    return render_template("about_us.html")





if __name__ == "__main__":
    app.run(debug=getDebug())