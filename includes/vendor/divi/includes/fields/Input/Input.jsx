// External Dependencies
import React, { Component } from 'react';

// Internal Dependencies
import './style.css';

class Input extends Component {

  static slug = 'stof_input';

  /**
   * Handle input value change.
   *
   * @param {object} event
   */
  _onChange = (event) => {
    this.props._onChange(this.props.name, event.target.value);
  }

  render() {
    return(
      <input
        id={`stof-input-${this.props.name}`}
        name={this.props.name}
        value={this.props.value}
        type='text'
        className='stof-input'
        onChange={this._onChange}
        placeholder='Your text here ...'
      />
    );
  }
}

export default Input;
