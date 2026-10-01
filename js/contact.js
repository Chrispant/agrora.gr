document.addEventListener("DOMContentLoaded",()=>{
    const form = document.getElementById("contact-form");
    const nameE1 = document.getElementById("contact-name");
    const emailE1 = document.getElementById("contact-email");
    const messageE1 = document.getElementById("contact-message");

    const nameErrorE1 = document.getElementById("contact-name-error");
    const emailErrorE1 = document.getElementById("contact-email-error");
    const messageErrorE1 = document.getElementById("contact-message-error");

    function clearErrors(){
        nameErrorE1.textContent = "";
        emailErrorE1.textContent = "";
        messageErrorE1.textContent = "";

        nameE1.classList.remove("input-error");
        emailE1.classList.remove("input-error");
        messageE1.classList.remove("input-error");
    }

    function showError(field,errorE1, message){
        errorE1.textContent = message;
        field.classList.add("input-error");



    }

    form.addEventListener("submit",async (e)=>{
        e.preventDefault();
        clearErrors();
        const name = nameE1.value.trim();
        const email = emailE1.value.trim();
        const message = messageE1.value.trim();
        
      

        if(name.length < 3){
            showError(nameE1,nameErrorE1, "Το όνομα πρέπει να είναι τουλάχιστον 3 χαρακτήρες.");
            return;
        }
        if (/\d/.test(name)){
            showError(nameE1,nameErrorE1, "Το όνομα δεν πρεπει να περιέχει αριθμούς.");
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!emailRegex.test(email)){
            showError(emailE1, emailErrorE1, "Παρακαλώ εισάγετε ένα έγκυρο email.");
            return;
        }

        if(message.length < 10){
            showError(messageE1, messageErrorE1, "Το μήνυμα πρέπει να είναι τουλάχιστον 10 χαρακτήρες.");
            return;
        }


            
        
       

        try {
        const res = await fetch('https://agrora-api-production.up.railway.app/api/contact', {
            method: 'POST',
            headers: {
            'Content-Type': 'application/json',
            },
            body: JSON.stringify({
            name: name,
            email: email,
            message: message,
            }),
        });

        const data = await res.json();
        if (!res.ok) {
         throw new Error('Network response was not ok');
        }
        if (data.ok || res.ok) {
            console.log('Success:', data);
            alert('Μήνυμα στάλθηκε επιτυχώς!');
            form.reset();
        } else {
            console.error('Error:', data.error);
            showError(messageE1, messageErrorE1, data.error || 'Σφάλμα κατά την αποστολή.');
        }
        }        
        catch (error) {
            console.error('Fetch error:', error);
            showError(messageE1, messageErrorE1, 'Σφάλμα δικτύου. Δοκιμάστε ξανά.');
            }
        


    });

});