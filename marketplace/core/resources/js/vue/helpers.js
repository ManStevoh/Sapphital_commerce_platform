export function getCurrencySymbolWithAmount(amount){
    const currency = window.currencySymbol !== undefined ? window.currencySymbol : {currencyPosition: 'left', symbol: ''};

    if(currency.currencyPosition ?? 'left' === 'left'){
        return currency.symbol + amount;
    }

    return amount + currency.symbol;
}
