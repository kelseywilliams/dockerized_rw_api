
var httpRequest;
httpRequest = new XMLHttpRequest();

if (!httpRequest) {
    alert('Internal Error.  Your key was likely sent, however this page cannot provid confirmation.  Please check your email.  Developer notes: Cannot create an XMLHTTP instance in script.js');
}
httpRequest.onreadystatechange = () => {
    if (httpRequest.readyState === XMLHttpRequest.DONE) {
        if (httpRequest.status === 200 && httpRequest.responseText !== "empty") {
            alert(httpRequest.responseText);
        }
    }
}
httpRequest.open('GET', "status.php");
httpRequest.send();
