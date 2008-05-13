function is_numeric(sText)
{
    var ValidChars  = "0123456789.";
    var IsNumber    = true;
    var Char;

 
    for (idex = 0; idex < sText.length && IsNumber == true; idex++)
    {
        Char = sText.charAt(idex);
        if (ValidChars.indexOf(Char) == -1)
        {
            IsNumber = false;
            break;
        }
    }
    return IsNumber;
}


